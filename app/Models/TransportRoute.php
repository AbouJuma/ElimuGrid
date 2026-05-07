<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransportRoute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'start_point',
        'end_point',
        'distance_km',
        'departure_time',
        'return_time',
        'school_id'
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('school', function ($query) {
            if (Auth::check() && Auth::user()->school_id) {
                $query->where('school_id', Auth::user()->school_id);
            }
        });
    }

    public function scopeOwner($query)
    {
        return $query->where('school_id', Auth::user()->school_id);
    }

    public function stops()
    {
        return $this->hasMany(TransportStop::class, 'route_id');
    }

    public function fees()
    {
        return $this->hasMany(TransportFee::class, 'route_id');
    }

    public function activeFee()
    {
        return $this->hasOne(TransportFee::class, 'route_id')
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            })
            ->whereNull('deleted_at')
            ->latest();
    }

    public function allocations()
    {
        return $this->hasMany(TransportAllocation::class, 'route_id');
    }

    public function assignments()
    {
        return $this->hasMany(TransportRouteAssignment::class, 'route_id');
    }
}
