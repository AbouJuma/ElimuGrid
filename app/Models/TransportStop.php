<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransportStop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'route_id',
        'name',
        'description',
        'pickup_time',
        'drop_time',
        'distance_from_start',
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

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function fee()
    {
        return $this->hasOne(TransportFee::class, 'stop_id');
    }

    public function allocations()
    {
        return $this->hasMany(TransportAllocation::class, 'stop_id');
    }
}
