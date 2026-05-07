<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransportFee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'route_id',
        'stop_id',
        'amount',
        'billing_cycle',
        'effective_from',
        'effective_until',
        'description',
        'school_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
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
        return $query->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            })
            ->whereNull('deleted_at');
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function stop()
    {
        return $this->belongsTo(TransportStop::class, 'stop_id');
    }

    public function allocations()
    {
        return $this->hasMany(TransportAllocation::class, 'transport_fee_id');
    }

    public function charges()
    {
        return $this->hasMany(TransportFeeCharge::class, 'transport_fee_id');
    }

    /**
     * Check if fee already charged for period
     */
    public function isChargedForPeriod($studentId, $period)
    {
        return TransportFeeCharge::where('transport_fee_id', $this->id)
            ->where('student_id', $studentId)
            ->where('period', $period)
            ->whereIn('status', ['pending', 'paid', 'partial'])
            ->exists();
    }
}
