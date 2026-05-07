<?php

namespace App\Http\Controllers;

use App\Repositories\Book\BookInterface;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BookController extends Controller
{
    private BookInterface $book;

    public function __construct(BookInterface $book)
    {
        $this->book = $book;
    }

    /**
     * Display a listing of books
     */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-list');
        
        $categories = $this->book->builder()
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response(view('library.books.index', compact('categories')));
    }

    /**
     * Get books for Bootstrap Table (AJAX)
     */
    public function getBooks(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-list');

        $query = $this->book->builder();

        // Apply filters
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', '%' . $search . '%')
                    ->orWhere('author', 'LIKE', '%' . $search . '%')
                    ->orWhere('isbn', 'LIKE', '%' . $search . '%');
            });
        }

        $books = $query->get();

        return response()->json([
            'total' => $books->count(),
            'rows' => $books->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'category' => $book->category,
                    'quantity' => $book->quantity,
                    'available_quantity' => $book->available_quantity,
                    'issued_quantity' => $book->issued_quantity,
                    'status' => $book->isAvailable() ? 'Available' : 'Out of Stock',
                ];
            })
        ]);
    }

    /**
     * Store a newly created book
     */
    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-create');

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|unique:books,isbn',
            'category' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        // Check if user has school_id
        if (!Auth::user()->school_id) {
            ResponseService::errorResponse('User does not have an associated school. Please set school_id for this user.');
        }

        try {
            DB::beginTransaction();

            $data = [
                'title' => $request->title,
                'author' => $request->author,
                'isbn' => $request->isbn,
                'category' => $request->category,
                'quantity' => $request->quantity,
                'available_quantity' => $request->quantity,
            ];

            $this->book->create($data);

            DB::commit();
            ResponseService::successResponse('Book added successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::errorResponse('Failed to add book', null, null, $e);
        }
    }

    /**
     * Show the form for editing a book
     */
    public function edit($id)
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-edit');

        $book = $this->book->findById($id);
        return response()->json($book);
    }

    /**
     * Update the specified book
     */
    public function update(Request $request, $id)
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-edit');

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|unique:books,isbn,' . $id,
            'category' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $book = $this->book->findById($id);
            
            // Calculate difference in quantity
            $oldQuantity = $book->quantity;
            $newQuantity = $request->quantity;
            $quantityDiff = $newQuantity - $oldQuantity;

            $data = [
                'title' => $request->title,
                'author' => $request->author,
                'isbn' => $request->isbn,
                'category' => $request->category,
                'quantity' => $newQuantity,
                'available_quantity' => $book->available_quantity + $quantityDiff,
            ];

            // Ensure available_quantity doesn't go negative
            if ($data['available_quantity'] < 0) {
                ResponseService::errorResponse('Cannot reduce quantity below issued amount');
            }

            $this->book->update($id, $data);

            DB::commit();
            ResponseService::successResponse('Book updated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::errorResponse('Failed to update book', null, null, $e);
        }
    }

    /**
     * Remove the specified book
     */
    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Library Management');
        ResponseService::noPermissionThenRedirect('book-delete');

        try {
            $book = $this->book->findById($id);
            
            // Check if book has active issues
            if ($book->activeIssues()->count() > 0) {
                ResponseService::errorResponse('Cannot delete book with active borrowings');
            }

            $this->book->delete($id);
            ResponseService::successResponse('Book deleted successfully');
        } catch (Throwable $e) {
            ResponseService::errorResponse('Failed to delete book', null, null, $e);
        }
    }

    /**
     * Search books (AJAX for book issue form)
     */
    public function search(Request $request)
    {
        $search = $request->get('q', '');
        $books = $this->book->search($search);

        return response()->json($books->map(function ($book) {
            return [
                'id' => $book->id,
                'text' => $book->title . ' by ' . $book->author . ' (ISBN: ' . $book->isbn . ')',
                'available' => $book->isAvailable(),
                'available_quantity' => $book->available_quantity,
            ];
        }));
    }
}
