<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

use App\Traits\TenantModel;

class Hostel extends Model
{
    use HasFactory, SoftDeletes, TenantModel;

    protected $fillable = [
        'name',
        'description',
        'school_id'
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
     * Get the rooms for this hostel
     */
    public function rooms()
    {
        return $this->hasMany(Room::class, 'hostel_id');
    }

    /**
     * Get the allocations for this hostel
     */
    public function allocations()
    {
        return $this->hasMany(HostelAllocation::class, 'hostel_id');
    }

    /**
     * Get active allocations count
     */
    public function getActiveAllocationsCountAttribute()
    {
        return $this->allocations()->where('status', 'active')->count();
    }

    /**
     * Get total rooms count
     */
    public function getTotalRoomsCountAttribute()
    {
        return $this->rooms()->count();
    }

    /**
     * Get total capacity
     */
    public function getTotalCapacityAttribute()
    {
        return $this->rooms()->sum('capacity');
    }

    /**
     * Get total occupied beds
     */
    public function getTotalOccupiedAttribute()
    {
        return $this->rooms()->sum('occupied_beds');
    }

    /**
     * Get available beds
     */
    public function getAvailableBedsAttribute()
    {
        return $this->total_capacity - $this->total_occupied;
    }
}
