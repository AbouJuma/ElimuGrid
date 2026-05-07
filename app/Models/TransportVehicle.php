<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransportVehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_number',
        'vehicle_type',
        'model',
        'make',
        'capacity',
        'registration_number',
        'insurance_expiry',
        'fitness_certificate_expiry',
        'status',
        'school_id'
    ];

    protected $casts = [
        'insurance_expiry' => 'date',
        'fitness_certificate_expiry' => 'date',
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
        return $this->hasMany(TransportRouteAssignment::class, 'vehicle_id');
    }

    public function getIsInsuranceExpiredAttribute()
    {
        return $this->insurance_expiry && $this->insurance_expiry < now();
    }

    public function getIsFitnessExpiredAttribute()
    {
        return $this->fitness_certificate_expiry && $this->fitness_certificate_expiry < now();
    }
}
