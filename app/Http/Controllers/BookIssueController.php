<?php

namespace App\Http\Controllers;

use App\Repositories\Book\BookInterface;
use App\Repositories\BookIssue\BookIssueInterface;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Repositories\User\UserInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BookIssueController extends Controller
{
    private BookIssueInterface $bookIssue;
    private BookInterface $book;
    private ClassSchoolInterface $classSchool;
    private UserInterface $user;
    private CachingService $cache;

    public function __construct(
        BookIssueInterface $bookIssue,
        BookInterface $book,
        ClassSchoolInterface $classSchool,
        UserInterface $user,
        CachingService $cache
    ) {
        $this->bookIssue = $bookIssue;
        $this->book = $book;
        $this->classSchool = $classSchool;
        $this->user = $user;
        $this->cache = $cache;
    }

    /**
     * Display book issue management page
     */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-issue-list');

        $classes = $this->classSchool->builder()->with(['stream', 'medium'])->orderBy('name', 'ASC')->get();
        $books = $this->book->getAvailableBooks();

        return response(view('library.book_issues.index', compact('classes', 'books')));
    }

    /**
     * Get students by class (AJAX)
     */
    public function getStudentsByClass(Request $request)
    {
        $classId = $request->get('class_id');

        if (!$classId) {
            return response()->json([]);
        }

        try {
            // Robust Eloquent approach: prefix-safe and Spatie-compatible
            $students = \App\Models\User::role('Student')
                ->whereHas('student', function ($query) use ($classId) {
                    $query->whereHas('class_section', function ($q) use ($classId) {
                        $q->where('class_id', $classId);
                    });
                })
                ->select('id', 'first_name', 'last_name', 'email')
                ->get();
        } catch (\Exception $e) {
            \Log::error('Failed to get students by class: ' . $e->getMessage());
            $students = collect();
        }

        return response()->json($students->map(function ($student) {
            return [
                'id' => $student->id,
                'text' => ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') . ' (' . ($student->email ?? '') . ')',
            ];
        }));
    }

    /**
     * Issue a book to student
     */
    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-issue-create');

        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:' . (new \App\Models\Book)->getTable() . ',id',
            'student_id' => 'required|exists:' . (new \App\Models\User)->getTable() . ',id',
            'class_id' => 'required|exists:' . (new \App\Models\ClassSchool)->getTable() . ',id',
            'issue_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:issue_date',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::connection('school')->beginTransaction();

            $book = $this->book->findById($request->book_id);
            $settings = $this->cache->getSchoolSettings();
            $maxBooksPerStudent = $settings['library_max_books_per_student'] ?? 3;

            // Check if book is available
            if (!$book->isAvailable()) {
                ResponseService::errorResponse('Book is not available for borrowing');
            }

            // Check if student already has active borrowing of this book
            if ($this->bookIssue->hasActiveBorrowing($request->student_id, $request->book_id)) {
                ResponseService::errorResponse('Student already has an active borrowing of this book');
            }

            // Check if student has reached maximum borrowing limit
            $activeBorrowings = $this->bookIssue->countActiveByStudent($request->student_id);
            if ($activeBorrowings >= $maxBooksPerStudent) {
                ResponseService::errorResponse('Student has reached maximum borrowing limit (' . $maxBooksPerStudent . ' books)');
            }

            // Create book issue
            $data = [
                'book_id' => $request->book_id,
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'issue_date' => $request->issue_date,
                'return_date' => $request->return_date,
                'status' => 'borrowed',
            ];

            $this->bookIssue->create($data);

            // Reduce available quantity
            $book->available_quantity--;
            $book->save();

            DB::connection('school')->commit();
            ResponseService::successResponse('Book issued successfully');
        } catch (Throwable $e) {
            DB::connection('school')->rollBack();
            ResponseService::errorResponse('Failed to issue book', null, null, $e);
        }
    }

    /**
     * Return a book
     */
    public function returnBook($id)
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-issue-return');

        try {
            DB::connection('school')->beginTransaction();

            // Get book issue using prefix-safe Eloquent
            $bookIssue = \App\Models\BookIssue::owner()->where('id', $id)->first();
            
            if (!$bookIssue) {
                return response()->json(['error' => 'Book issue not found'], 404);
            }

            // Check if already returned
            if ($bookIssue->status === 'returned') {
                return response()->json(['error' => 'Book is already returned'], 400);
            }

            $finePerDay = 500; // Fine per day
            
            // Mark as returned using prefix-safe model update
            $bookIssue->markAsReturned($finePerDay);

            // Increase book available quantity using prefix-safe model increment
            $book = \App\Models\Book::owner()->where('id', $bookIssue->book_id)->first();
            if ($book) {
                $book->available_quantity++;
                $book->save();
            }

            DB::connection('school')->commit();
            
            return response()->json([
                'message' => 'Book returned successfully',
                'success' => true
            ]);
            
        } catch (\Exception $e) {
            DB::connection('school')->rollBack();
            return response()->json(['error' => 'Failed to return book: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get borrowed books (AJAX for Bootstrap Table)
     */
    public function getBorrowedBooks(Request $request)
    {
        try {
            // Retrieve borrowed issues using prefix-safe Eloquent model relationships
            $issues = \App\Models\BookIssue::owner()
                ->borrowed()
                ->with(['book', 'student', 'classSchool'])
                ->get();
            
            $carbonToday = \Carbon\Carbon::today();
            $finePerDay = 500;
            
            return response()->json([
                'total' => $issues->count(),
                'rows' => $issues->map(function ($issue) use ($carbonToday, $finePerDay) {
                    $lateDays = $issue->calculateLateDays();
                    $fineAmount = $issue->calculateFineAmount($finePerDay);
                    
                    $studentName = $issue->student ? trim($issue->student->first_name . ' ' . $issue->student->last_name) : 'Unknown Student';
                    
                    return [
                        'id' => $issue->id,
                        'book_title' => $issue->book->title ?? 'Unknown Book',
                        'book_author' => $issue->book->author ?? 'Unknown Author',
                        'book_isbn' => $issue->book->isbn ?? '',
                        'student_name' => $studentName,
                        'class_name' => $issue->classSchool->name ?? 'Class 1',
                        'issue_date' => $issue->issue_date ? $issue->issue_date->format('Y-m-d') : '',
                        'return_date' => $issue->return_date ? $issue->return_date->format('Y-m-d') : '',
                        'late_days' => $lateDays,
                        'fine_amount' => number_format($fineAmount, 2),
                        'status' => $issue->status,
                        'status_badge' => '<span class="badge badge-info">Borrowed</span>',
                        'operate' => '<button class="btn btn-sm btn-success return-btn" data-id="' . $issue->id . '">Return</button>',
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get returned books (AJAX for Bootstrap Table)
     */
    public function getReturnedBooks(Request $request)
    {
        try {
            // Query returned issues using prefix-safe Eloquent model relationships
            $query = \App\Models\BookIssue::owner()
                ->returned()
                ->with(['book', 'student', 'classSchool']);

            // Apply date range filter
            if ($request->has('from_date') && $request->from_date) {
                $query->whereDate('actual_return_date', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date) {
                $query->whereDate('actual_return_date', '<=', $request->to_date);
            }

            // Apply other filters
            if ($request->has('class_id') && $request->class_id) {
                $query->where('class_id', $request->class_id);
            }

            if ($request->has('student_id') && $request->student_id) {
                $query->where('student_id', $request->student_id);
            }

            $issues = $query->orderBy('actual_return_date', 'desc')->get();

            return response()->json([
                'total' => $issues->count(),
                'rows' => $issues->map(function ($issue) {
                    $studentName = $issue->student ? trim($issue->student->first_name . ' ' . $issue->student->last_name) : 'Unknown Student';

                    return [
                        'id' => $issue->id,
                        'book_title' => $issue->book->title ?? 'Unknown Book',
                        'book_author' => $issue->book->author ?? 'Unknown Author',
                        'book_isbn' => $issue->book->isbn ?? '',
                        'student_name' => $studentName,
                        'class_name' => $issue->classSchool->name ?? 'Class 1',
                        'issue_date' => $issue->issue_date ? $issue->issue_date->format('Y-m-d') : '',
                        'return_date' => $issue->return_date ? $issue->return_date->format('Y-m-d') : '',
                        'actual_return_date' => $issue->actual_return_date ? $issue->actual_return_date->format('Y-m-d') : '-',
                        'late_days' => $issue->late_days ?? 0,
                        'fine_amount' => number_format($issue->fine_amount ?? 0, 2),
                        'status' => 'Returned',
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get overdue books (AJAX for Bootstrap Table)
     */
    public function getOverdueBooks(Request $request)
    {
        try {
            $today = date('Y-m-d');
            
            // Get both 'overdue' status books AND 'borrowed' books past their return date
            $query = \App\Models\BookIssue::owner()
                ->with(['book', 'student', 'classSchool'])
                ->where(function($q) use ($today) {
                    $q->where('status', 'overdue')
                      ->orWhere(function($q2) use ($today) {
                          $q2->where('status', 'borrowed')
                             ->whereDate('return_date', '<', $today);
                      });
                });

            // Apply filters
            if ($request->has('class_id') && $request->class_id) {
                $query->where('class_id', $request->class_id);
            }

            if ($request->has('student_id') && $request->student_id) {
                $query->where('student_id', $request->student_id);
            }

            $issues = $query->orderBy('return_date', 'asc')->get();

            $finePerDay = 500; // Fine per day
            
            return response()->json([
                'total' => $issues->count(),
                'rows' => $issues->map(function ($issue) use ($finePerDay) {
                    $lateDays = $issue->calculateLateDays();
                    $fineAmount = $issue->calculateFineAmount($finePerDay);
                    
                    $studentName = $issue->student ? trim($issue->student->first_name . ' ' . $issue->student->last_name) : 'Unknown Student';

                    return [
                        'id' => $issue->id,
                        'book_title' => $issue->book->title ?? 'Unknown Book',
                        'book_author' => $issue->book->author ?? 'Unknown Author',
                        'book_isbn' => $issue->book->isbn ?? '',
                        'student_name' => $studentName,
                        'student_email' => $issue->student->email ?? '',
                        'class_name' => $issue->classSchool->name ?? 'Class 1',
                        'issue_date' => $issue->issue_date ? $issue->issue_date->format('Y-m-d') : '',
                        'return_date' => $issue->return_date ? $issue->return_date->format('Y-m-d') : '',
                        'late_days' => $lateDays,
                        'fine_amount' => number_format($fineAmount, 2),
                        'status' => 'Overdue',
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reports page
     */
    public function reports()
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-report-view');

        $classes = $this->classSchool->builder()->orderBy('name', 'ASC')->get();
        $books = $this->book->all();

        $stats = [
            'total_books' => $this->book->all()->sum('quantity'),
            'available_books' => $this->book->all()->sum('available_quantity'),
            'borrowed_books' => $this->bookIssue->getActiveIssues()->count(),
            'overdue_books' => $this->bookIssue->getOverdueIssues()->count(),
        ];

        return response(view('library.reports.index', compact('classes', 'books', 'stats')));
    }
}
