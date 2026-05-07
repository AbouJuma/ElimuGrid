<?php

namespace App\Repositories\BookIssue;

use App\Repositories\Base\BaseInterface;

interface BookIssueInterface extends BaseInterface {
    /**
     * Get issues by student
     * @param int $studentId
     * @return mixed
     */
    public function getByStudent(int $studentId);

    /**
     * Get issues by book
     * @param int $bookId
     * @return mixed
     */
    public function getByBook(int $bookId);

    /**
     * Get active issues (borrowed or overdue)
     * @return mixed
     */
    public function getActiveIssues();

    /**
     * Get overdue issues
     * @return mixed
     */
    public function getOverdueIssues();

    /**
     * Get issues by class
     * @param int $classId
     * @return mixed
     */
    public function getByClass(int $classId);

    /**
     * Check if student has active borrowing of specific book
     * @param int $studentId
     * @param int $bookId
     * @return bool
     */
    public function hasActiveBorrowing(int $studentId, int $bookId): bool;

    /**
     * Get count of active borrowings by student
     * @param int $studentId
     * @return int
     */
    public function countActiveByStudent(int $studentId): int;
}
