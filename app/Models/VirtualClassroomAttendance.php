<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\TenantModel;

class VirtualClassroomAttendance extends Model
{
    use HasFactory, TenantModel;

    protected $table = 'virtual_classroom_attendance';

    protected $fillable = [
        'virtual_classroom_id',
        'student_id',
        'joined_at',
        'left_at',
        'duration',
        'school_id',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set school_id on create
        static::creating(function ($model) {
            if (empty($model->school_id) && Auth::check()) {
                $model->school_id = Auth::user()->school_id;
            }
        });

        // Add school_id scope to all queries
        static::addGlobalScope('school', function ($query) {
            if (Auth::check() && Auth::user()->school_id) {
                $query->where('school_id', Auth::user()->school_id);
            }
        });
    }

    /**
     * Get the virtual classroom that owns the attendance
     */
    public function virtualClassroom()
    {
        return $this->belongsTo(VirtualClassroom::class);
    }

    /**
     * Get the student that owns the attendance
     */
    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    /**
     * Get the user through student relationship
     */
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

    /**
     * Calculate duration when student leaves
     */
    public function calculateDuration()
    {
        if ($this->joined_at && $this->left_at) {
            $this->duration = $this->joined_at->diffInMinutes($this->left_at);
            $this->save();
        }
        return $this->duration;
    }

    /**
     * Record student joining
     */
    public static function recordJoin($virtualClassroomId, $studentId)
    {
        $schoolId = Auth::user()->school_id ?? null;

        return self::create([
            'virtual_classroom_id' => $virtualClassroomId,
            'student_id' => $studentId,
            'joined_at' => now(),
            'school_id' => $schoolId,
        ]);
    }

    /**
     * Record student leaving
     */
    public function recordLeave()
    {
        $this->left_at = now();
        $this->calculateDuration();
        return $this;
    }

    /**
     * Scope for attendance by virtual classroom
     */
    public function scopeByVirtualClassroom($query, $virtualClassroomId)
    {
        return $query->where('virtual_classroom_id', $virtualClassroomId);
    }

    /**
     * Scope for attendance by student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Get total duration in human readable format
     */
    public function getDurationFormattedAttribute()
    {
        if ($this->duration < 60) {
            return $this->duration . ' min';
        }
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        return $hours . 'h ' . $minutes . 'm';
    }
}
