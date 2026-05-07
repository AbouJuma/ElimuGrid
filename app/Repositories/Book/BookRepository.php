<?php

namespace App\Repositories\Book;

use App\Models\Book;
use App\Repositories\Saas\SaaSRepository;

class BookRepository extends SaaSRepository implements BookInterface
{
    public function __construct(Book $model)
    {
        parent::__construct($model);
    }

    /**
     * Get books by category
     * @param string $category
     * @return mixed
     */
    public function getByCategory(string $category)
    {
        return $this->defaultModel()->where('category', $category)->get();
    }

    /**
     * Get available books (available_quantity > 0)
     * @return mixed
     */
    public function getAvailableBooks()
    {
        return $this->defaultModel()->where('available_quantity', '>', 0)->get();
    }

    /**
     * Search books by title or author
     * @param string $search
     * @return mixed
     */
    public function search(string $search)
    {
        return $this->defaultModel()
            ->where(function ($query) use ($search) {
                $query->where('title', 'LIKE', '%' . $search . '%')
                    ->orWhere('author', 'LIKE', '%' . $search . '%')
                    ->orWhere('isbn', 'LIKE', '%' . $search . '%');
            })
            ->get();
    }
}
