<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

use App\Traits\TenantModel;

class Room extends Model
{
    use HasFactory, SoftDeletes, TenantModel;

    protected $fillable = [
        'hostel_id',
        'room_number',
        'capacity',
        'occupied_beds',
        'school_id'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'occupied_beds' => 'integer',
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
     * Get the hostel for this room
     */
    public function hostel()
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }

    /**
     * Get the allocations for this room
     */
    public function allocations()
    {
        return $this->hasMany(HostelAllocation::class, 'room_id');
    }

    /**
     * Get active allocations
     */
    public function activeAllocations()
    {
        return $this->allocations()->where('status', 'active');
    }

    /**
     * Check if room has available beds
     */
    public function hasAvailableBeds(): bool
    {
        return $this->occupied_beds < $this->capacity;
    }

    /**
     * Get available beds count
     */
    public function getAvailableBedsAttribute(): int
    {
        return $this->capacity - $this->occupied_beds;
    }

    /**
     * Get occupancy percentage
     */
    public function getOccupancyPercentageAttribute(): float
    {
        if ($this->capacity == 0) {
            return 0;
        }
        return round(($this->occupied_beds / $this->capacity) * 100, 2);
    }
}
