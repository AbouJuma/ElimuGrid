<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransportAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'class_id',
        'route_id',
        'stop_id',
        'transport_fee_id',
        'allocation_date',
        'end_date',
        'trip_type',
        'status',
        'auto_charge',
        'school_id'
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'end_date' => 'date',
        'auto_charge' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('school', function ($query) {
            if (Auth::check() && Auth::user()->school_id) {
                $query->where('school_id', Auth::user()->school_id);
            }
        });

        static::creating(function ($model) {
            $model->school_id = Auth::user()->school_id ?? $model->school_id;
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

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            Students::class,
            'id',
            'id',
            'student_id',
            'user_id'
        );
    }

    public function classSchool()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function stop()
    {
        return $this->belongsTo(TransportStop::class, 'stop_id');
    }

    public function transportFee()
    {
        return $this->belongsTo(TransportFee::class, 'transport_fee_id');
    }

    public function charges()
    {
        return $this->hasMany(TransportFeeCharge::class, 'allocation_id');
    }

    public function getCurrentFeeAttribute()
    {
        if ($this->transport_fee_id) {
            return $this->transportFee;
        }
        return $this->route?->activeFee;
    }

    /**
     * Get current period based on billing cycle
     */
    public function getCurrentPeriod(): string
    {
        $fee = $this->current_fee;
        if (!$fee) {
            return now()->format('Y-m'); // Default monthly
        }

        return match($fee->billing_cycle) {
            'monthly' => now()->format('Y-m'),
            'term' => $this->getCurrentTermPeriod(),
            'yearly' => now()->format('Y'),
            default => now()->format('Y-m'),
        };
    }

    private function getCurrentTermPeriod(): string
    {
        $month = now()->month;
        $year = now()->year;
        
        // Simple term logic - adjust based on your school's term structure
        if ($month >= 1 && $month <= 4) {
            return $year . '-Term1';
        } elseif ($month >= 5 && $month <= 8) {
            return $year . '-Term2';
        } else {
            return $year . '-Term3';
        }
    }

    /**
     * Check if already charged for current period
     */
    public function isChargedForCurrentPeriod(): bool
    {
        $period = $this->getCurrentPeriod();
        return TransportFeeCharge::where('allocation_id', $this->id)
            ->where('period', $period)
            ->whereIn('status', ['pending', 'paid', 'partial'])
            ->exists();
    }
}
