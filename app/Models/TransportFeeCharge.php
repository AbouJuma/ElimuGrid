<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransportFeeCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'allocation_id',
        'student_id',
        'route_id',
        'transport_fee_id',
        'amount',
        'period',
        'due_date',
        'paid_date',
        'paid_amount',
        'status',
        'invoice_id',
        'notes',
        'school_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    public function scopeForPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function allocation()
    {
        return $this->belongsTo(TransportAllocation::class, 'allocation_id');
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function transportFee()
    {
        return $this->belongsTo(TransportFee::class, 'transport_fee_id');
    }

    public function invoice()
    {
        return $this->belongsTo(FeesType::class, 'invoice_id');
    }

    public function getBalanceAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'pending' && $this->due_date < now();
    }
}
