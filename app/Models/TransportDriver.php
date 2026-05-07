<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransportDriver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'license_number',
        'license_expiry',
        'emergency_contact',
        'status',
        'school_id'
    ];

    protected $casts = [
        'license_expiry' => 'date',
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
        return $query->where('status', 'active');
    }

    public function assignments()
    {
        return $this->hasMany(TransportRouteAssignment::class, 'driver_id');
    }

    public function getIsLicenseExpiredAttribute()
    {
        return $this->license_expiry && $this->license_expiry < now();
    }
}
