<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookIssue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'book_id',
        'student_id',
        'class_id',
        'issue_date',
        'return_date',
        'actual_return_date',
        'late_days',
        'fine_amount',
        'status',
        'school_id'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'return_date' => 'date',
        'actual_return_date' => 'date',
        'late_days' => 'integer',
        'fine_amount' => 'decimal:2',
    ];

    /**
     * Get the book for this issue
     */
    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    /**
     * Get the student for this issue
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the class for this issue
     */
    public function classSchool()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    /**
     * Scope to filter by school (tenant isolation)
     */
    public function scopeOwner($query)
    {
        if (Auth::user()) {
            if (Auth::user()->school_id) {
                return $query->where('school_id', Auth::user()->school_id);
            }
        }
        return $query;
    }

    /**
     * Scope for borrowed books
     */
    public function scopeBorrowed($query)
    {
        return $query->where('status', 'borrowed');
    }

    /**
     * Scope for returned books
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    /**
     * Scope for overdue books
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    /**
     * Scope for active issues (borrowed or overdue)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['borrowed', 'overdue']);
    }

    /**
     * Calculate late days based on return_date and current date or actual_return_date
     */
    public function calculateLateDays(): int
    {
        if ($this->status === 'returned') {
            // If returned, calculate based on actual return date
            if ($this->actual_return_date && $this->actual_return_date->gt($this->return_date)) {
                return $this->actual_return_date->diffInDays($this->return_date);
            }
            return 0;
        }

        // If not returned, calculate based on today
        $today = Carbon::today();
        if ($today->gt($this->return_date)) {
            return $today->diffInDays($this->return_date);
        }

        return 0;
    }

    /**
     * Calculate fine amount based on late days and fine per day
     */
    public function calculateFineAmount(float $finePerDay): float
    {
        $lateDays = $this->calculateLateDays();
        return $lateDays * $finePerDay;
    }

    /**
     * Update status to overdue if applicable
     */
    public function updateOverdueStatus(): void
    {
        if ($this->status === 'borrowed' && Carbon::today()->gt($this->return_date)) {
            $this->status = 'overdue';
            $this->save();
        }
    }

    /**
     * Mark as returned
     */
    public function markAsReturned(float $finePerDay): void
    {
        $this->actual_return_date = Carbon::today();
        $this->late_days = $this->calculateLateDays();
        $this->fine_amount = $this->calculateFineAmount($finePerDay);
        $this->status = 'returned';
        $this->save();
    }

    /**
     * Check if this issue is active (not returned)
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['borrowed', 'overdue']);
    }
}
