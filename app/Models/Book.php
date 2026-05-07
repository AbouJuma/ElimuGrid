<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'category',
        'quantity',
        'available_quantity',
        'school_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'available_quantity' => 'integer',
    ];

    /**
     * Get the book issues for this book
     */
    public function bookIssues()
    {
        return $this->hasMany(BookIssue::class, 'book_id');
    }

    /**
     * Get active (borrowed/overdue) book issues
     */
    public function activeIssues()
    {
        return $this->hasMany(BookIssue::class, 'book_id')
            ->whereIn('status', ['borrowed', 'overdue']);
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
     * Check if book is available for borrowing
     */
    public function isAvailable(): bool
    {
        return $this->available_quantity > 0;
    }

    /**
     * Get issued quantity
     */
    public function getIssuedQuantityAttribute(): int
    {
        return $this->quantity - $this->available_quantity;
    }
}
