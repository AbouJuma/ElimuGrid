<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransportRouteAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'route_id',
        'vehicle_id',
        'driver_id',
        'trip_type',
        'assignment_date',
        'end_date',
        'status',
        'school_id'
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'end_date' => 'date',
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNull('end_date')
            ->orWhere('end_date', '>=', now());
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(TransportDriver::class, 'driver_id');
    }
}
