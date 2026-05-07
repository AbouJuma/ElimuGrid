<?php

namespace App\Repositories\Book;

use App\Repositories\Base\BaseInterface;

interface BookInterface extends BaseInterface {
    /**
     * Get books by category
     * @param string $category
     * @return mixed
     */
    public function getByCategory(string $category);

    /**
     * Get available books
     * @return mixed
     */
    public function getAvailableBooks();

    /**
     * Search books by title or author
     * @param string $search
     * @return mixed
     */
    public function search(string $search);
}
