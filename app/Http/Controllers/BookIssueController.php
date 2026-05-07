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

        $classes = $this->classSchool->builder()->orderBy('name', 'ASC')->get();
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

        // Try multiple approaches to get students by class
        $students = collect();
        
        // Method 1: Direct query through students table (without school_id filter since we're in school DB)
        try {
            $studentUsers = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->join('students', 'users.id', '=', 'students.user_id')
                ->join('class_sections', 'students.class_section_id', '=', 'class_sections.id')
                ->where('roles.name', 'Student')
                ->where('class_sections.class_id', $classId)
                ->whereNull('users.deleted_at')
                ->whereNull('students.deleted_at')
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->get();
            
            $students = $studentUsers;
        } catch (\Exception $e) {
            // Method 2: Use Eloquent relationships
            try {
                $students = $this->user->builder()
                    ->role('Student')
                    ->whereHas('student', function ($query) use ($classId) {
                        $query->where('class_id', $classId);
                    })
                    ->select('id', 'first_name', 'last_name', 'email')
                    ->get();
            } catch (\Exception $e2) {
                // Method 3: Simple query through class_sections
                try {
                    $classSections = DB::table('class_sections')
                        ->where('class_id', $classId)
                        ->pluck('id');
                    
                    if ($classSections->isNotEmpty()) {
                        $studentUsers = DB::table('users')
                            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                            ->join('students', 'users.id', '=', 'students.user_id')
                            ->where('roles.name', 'Student')
                            ->whereIn('students.class_section_id', $classSections)
                            ->whereNull('users.deleted_at')
                            ->whereNull('students.deleted_at')
                            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                            ->get();
                        
                        $students = $studentUsers;
                    }
                } catch (\Exception $e3) {
                    \Log::error('Failed to get students by class: ' . $e3->getMessage());
                }
            }
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
            'book_id' => 'required|exists:books,id',
            'student_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:class_schools,id',
            'issue_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:issue_date',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

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

            DB::commit();
            ResponseService::successResponse('Book issued successfully');
        } catch (Throwable $e) {
            DB::rollBack();
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
            DB::beginTransaction();

            // Get book issue using direct query
            $bookIssue = DB::table('book_issues')->where('id', $id)->first();
            
            if (!$bookIssue) {
                return response()->json(['error' => 'Book issue not found'], 404);
            }

            // Check if already returned
            if ($bookIssue->status === 'returned') {
                return response()->json(['error' => 'Book is already returned'], 400);
            }

            // Calculate late days and fine
            $today = now();
            $returnDate = $bookIssue->return_date;
            $lateDays = 0;
            $fineAmount = 0;
            
            if ($today->gt($returnDate)) {
                $lateDays = $today->diffInDays($returnDate);
                $finePerDay = 500; // Fine per day
                $fineAmount = $lateDays * $finePerDay;
            }
            
            // Update book issue status with late days and fine
            DB::table('book_issues')
                ->where('id', $id)
                ->update([
                    'status' => 'returned',
                    'actual_return_date' => $today,
                    'late_days' => $lateDays,
                    'fine_amount' => $fineAmount,
                    'updated_at' => $today
                ]);

            // Increase book available quantity
            DB::table('books')
                ->where('id', $bookIssue->book_id)
                ->increment('available_quantity');

            DB::commit();
            
            return response()->json([
                'message' => 'Book returned successfully',
                'success' => true
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to return book: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get borrowed books (AJAX for Bootstrap Table)
     */
    public function getBorrowedBooks(Request $request)
    {
        // Get real data with users and class joins using school database connection
        try {
            $issues = DB::connection('school')->table('book_issues as bi')
                ->leftJoin('books as b', 'bi.book_id', '=', 'b.id')
                ->leftJoin('users as u', 'bi.student_id', '=', 'u.id')
                ->leftJoin('classes as c', 'bi.class_id', '=', 'c.id')
                ->select('bi.id', 'bi.class_id', 'bi.student_id', 'bi.book_id', 'bi.issue_date', 'bi.return_date', 'bi.status',
                        'b.title as book_title', 'b.author as book_author', 'b.isbn as book_isbn',
                        'u.first_name', 'u.last_name', 'u.email', 'c.name as class_name')
                ->where('bi.status', 'borrowed')
                ->get();
            
            // Calculate late days in real-time for display
            $carbonToday = \Carbon\Carbon::today();
            $finePerDay = 500;
            
            return response()->json([
                'total' => $issues->count(),
                'rows' => $issues->map(function ($issue) use ($carbonToday, $finePerDay) {
                    // Calculate real-time late days for borrowed books
                    $returnDate = \Carbon\Carbon::parse($issue->return_date);
                    $lateDays = 0;
                    $fineAmount = 0;
                    if ($carbonToday->gt($returnDate)) {
                        $lateDays = $carbonToday->diffInDays($returnDate);
                        $fineAmount = $lateDays * $finePerDay;
                    }
                    
                    return [
                        'id' => $issue->id,
                        'book_title' => $issue->book_title ?? 'Unknown Book',
                        'book_author' => $issue->book_author ?? 'Unknown Author',
                        'book_isbn' => $issue->book_isbn ?? '',
                        'student_name' => trim(($issue->first_name ?? '') . ' ' . ($issue->last_name ?? '')) ?: 'Unknown Student',
                        'class_name' => $issue->class_name ?? 'Class 1',
                        'issue_date' => $issue->issue_date,
                        'return_date' => $issue->return_date,
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
            $query = DB::connection('school')->table('book_issues as bi')
                ->leftJoin('books as b', 'bi.book_id', '=', 'b.id')
                ->leftJoin('users as u', 'bi.student_id', '=', 'u.id')
                ->leftJoin('classes as c', 'bi.class_id', '=', 'c.id')
                ->select('bi.*', 'b.title as book_title', 'b.author as book_author', 'b.isbn as book_isbn',
                        'u.first_name', 'u.last_name', 'u.email', 'c.name as class_name')
                ->where('bi.status', 'returned');

            // Apply date range filter
            if ($request->has('from_date') && $request->from_date) {
                $query->whereDate('bi.actual_return_date', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date) {
                $query->whereDate('bi.actual_return_date', '<=', $request->to_date);
            }

            // Apply other filters
            if ($request->has('class_id') && $request->class_id) {
                $query->where('bi.class_id', $request->class_id);
            }

            if ($request->has('student_id') && $request->student_id) {
                $query->where('bi.student_id', $request->student_id);
            }

            $issues = $query->orderBy('bi.actual_return_date', 'desc')->get();

            return response()->json([
                'total' => $issues->count(),
                'rows' => $issues->map(function ($issue) {
                    return [
                        'id' => $issue->id,
                        'book_title' => $issue->book_title ?? 'Unknown Book',
                        'book_author' => $issue->book_author ?? 'Unknown Author',
                        'book_isbn' => $issue->book_isbn ?? '',
                        'student_name' => trim(($issue->first_name ?? '') . ' ' . ($issue->last_name ?? '')) ?: 'Unknown Student',
                        'class_name' => $issue->class_name ?? 'Class 1',
                        'issue_date' => $issue->issue_date,
                        'return_date' => $issue->return_date,
                        'actual_return_date' => $issue->actual_return_date ?? '-',
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

    /**0
     * Get overdue books (AJAX for Bootstrap Table)
     */
    public function getOverdueBooks(Request $request)
    {
        try {
            $today = date('Y-m-d');
            
            // Get both 'overdue' status books AND 'borrowed' books past their return date
            $query = DB::connection('school')->table('book_issues as bi')
                ->leftJoin('books as b', 'bi.book_id', '=', 'b.id')
                ->leftJoin('users as u', 'bi.student_id', '=', 'u.id')
                ->leftJoin('classes as c', 'bi.class_id', '=', 'c.id')
                ->select('bi.*', 'b.title as book_title', 'b.author as book_author', 'b.isbn as book_isbn',
                        'u.first_name', 'u.last_name', 'u.email', 'c.name as class_name')
                ->where(function($q) use ($today) {
                    $q->where('bi.status', 'overdue')
                      ->orWhere(function($q2) use ($today) {
                          $q2->where('bi.status', 'borrowed')
                             ->whereDate('bi.return_date', '<', $today);
                      });
                });

            // Apply filters
            if ($request->has('class_id') && $request->class_id) {
                $query->where('bi.class_id', $request->class_id);
            }

            if ($request->has('student_id') && $request->student_id) {
                $query->where('bi.student_id', $request->student_id);
            }

            $issues = $query->orderBy('bi.return_date', 'asc')->get();

            // Calculate late days and fines in real-time for display
            $carbonToday = \Carbon\Carbon::today();
            $finePerDay = 500; // Fine per day
            
            return response()->json([
                'total' => $issues->count(),
                'rows' => $issues->map(function ($issue) use ($carbonToday, $finePerDay) {
                    // Calculate real-time late days
                    $returnDate = \Carbon\Carbon::parse($issue->return_date);
                    $lateDays = 0;
                    if ($carbonToday->gt($returnDate)) {
                        $lateDays = $carbonToday->diffInDays($returnDate);
                    }
                    $fineAmount = $lateDays * $finePerDay;
                    
                    return [
                        'id' => $issue->id,
                        'book_title' => $issue->book_title ?? 'Unknown Book',
                        'book_author' => $issue->book_author ?? 'Unknown Author',
                        'book_isbn' => $issue->book_isbn ?? '',
                        'student_name' => trim(($issue->first_name ?? '') . ' ' . ($issue->last_name ?? '')) ?: 'Unknown Student',
                        'student_email' => $issue->email ?? '',
                        'class_name' => $issue->class_name ?? 'Class 1',
                        'issue_date' => $issue->issue_date,
                        'return_date' => $issue->return_date,
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
