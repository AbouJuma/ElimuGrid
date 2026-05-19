<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\TenantModel;

class VirtualClassroom extends Model
{
    use HasFactory, SoftDeletes, TenantModel;

    protected $fillable = [
        'title',
        'description',
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'room_name',
        'meeting_url',
        'start_time',
        'end_time',
        'status',
        'created_by',
        'school_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
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
     * Get the class that owns the VirtualClassroom
     */
    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    /**
     * Get the section that owns the VirtualClassroom
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Get the subject that owns the VirtualClassroom
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Get the teacher that owns the VirtualClassroom
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the creator that owns the VirtualClassroom
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all attendance records for this virtual classroom
     */
    public function attendance()
    {
        return $this->hasMany(VirtualClassroomAttendance::class);
    }

    /**
     * Scope for scheduled sessions
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for live sessions
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    /**
     * Scope for completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for upcoming sessions
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
            ->whereIn('status', ['scheduled']);
    }

    /**
     * Scope for today's sessions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_time', today());
    }

    /**
     * Scope for sessions by class
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope for sessions by teacher
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Check if session is currently live
     */
    public function isLive()
    {
        return $this->status === 'live' ||
            ($this->status === 'scheduled' &&
             $this->start_time <= now() &&
             $this->end_time >= now());
    }

    /**
     * Check if session is upcoming
     */
    public function isUpcoming()
    {
        return $this->status === 'scheduled' && $this->start_time > now();
    }

    /**
     * Check if session has ended
     */
    public function hasEnded()
    {
        return $this->status === 'completed' || $this->end_time < now();
    }

    /**
     * Generate a unique room name
     */
    public static function generateRoomName($schoolId, $title)
    {
        $prefix = 'elimugrid';
        $schoolCode = substr(md5($schoolId), 0, 6);
        $sessionCode = substr(md5($title . time()), 0, 8);
        return strtolower("{$prefix}-{$schoolCode}-{$sessionCode}");
    }
}
