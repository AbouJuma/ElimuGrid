<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HostelAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'class_id',
        'hostel_id',
        'room_id',
        'bed_number',
        'allocation_date',
        'checkout_date',
        'status',
        'school_id'
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'checkout_date' => 'date',
    ];

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
     * Scope for active allocations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for checked out allocations
     */
    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out');
    }

    /**
     * Get the student for this allocation
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the class for this allocation
     */
    public function classSchool()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    /**
     * Get the hostel for this allocation
     */
    public function hostel()
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }

    /**
     * Get the room for this allocation
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Check if allocation is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get duration in days
     */
    public function getDurationAttribute(): int
    {
        $start = Carbon::parse($this->allocation_date);
        $end = $this->checkout_date ? Carbon::parse($this->checkout_date) : Carbon::today();
        return $start->diffInDays($end);
    }

    /**
     * Mark as checked out
     */
    public function checkOut(): void
    {
        $this->status = 'checked_out';
        $this->checkout_date = Carbon::today();
        $this->save();
    }
}
