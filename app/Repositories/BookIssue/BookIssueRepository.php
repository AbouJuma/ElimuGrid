<?php

namespace App\Repositories\BookIssue;

use App\Models\BookIssue;
use App\Repositories\Saas\SaaSRepository;

class BookIssueRepository extends SaaSRepository implements BookIssueInterface
{
    public function __construct(BookIssue $model)
    {
        parent::__construct($model);
    }

    /**
     * Get issues by student
     * @param int $studentId
     * @return mixed
     */
    public function getByStudent(int $studentId)
    {
        return $this->defaultModel()->where('student_id', $studentId)->get();
    }

    /**
     * Get issues by book
     * @param int $bookId
     * @return mixed
     */
    public function getByBook(int $bookId)
    {
        return $this->defaultModel()->where('book_id', $bookId)->get();
    }

    /**
     * Get active issues (borrowed or overdue)
     * @return mixed
     */
    public function getActiveIssues()
    {
        return $this->defaultModel()
            ->whereIn('status', ['borrowed', 'overdue'])
            ->with(['book', 'student', 'classSchool'])
            ->get();
    }

    /**
     * Get overdue issues (including borrowed books past return date)
     * @return mixed
     */
    public function getOverdueIssues()
    {
        $today = date('Y-m-d');
        return $this->defaultModel()
            ->where(function($query) use ($today) {
                $query->where('status', 'overdue')
                      ->orWhere(function($q) use ($today) {
                          $q->where('status', 'borrowed')
                            ->whereDate('return_date', '<', $today);
                      });
            })
            ->with(['book', 'student', 'classSchool'])
            ->get();
    }

    /**
     * Get issues by class
     * @param int $classId
     * @return mixed
     */
    public function getByClass(int $classId)
    {
        return $this->defaultModel()->where('class_id', $classId)->get();
    }

    /**
     * Check if student has active borrowing of specific book
     * @param int $studentId
     * @param int $bookId
     * @return bool
     */
    public function hasActiveBorrowing(int $studentId, int $bookId): bool
    {
        return $this->defaultModel()
            ->where('student_id', $studentId)
            ->where('book_id', $bookId)
            ->whereIn('status', ['borrowed', 'overdue'])
            ->exists();
    }

    /**
     * Get count of active borrowings by student
     * @param int $studentId
     * @return int
     */
    public function countActiveByStudent(int $studentId): int
    {
        return $this->defaultModel()
            ->where('student_id', $studentId)
            ->whereIn('status', ['borrowed', 'overdue'])
            ->count();
    }
}
