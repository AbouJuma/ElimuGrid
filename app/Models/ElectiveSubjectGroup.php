<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\TenantModel;

class ElectiveSubjectGroup extends Model
{
    use HasFactory, SoftDeletes, TenantModel;

    protected $fillable = [
        'total_subjects',
        'total_selectable_subjects',
        'class_id',
        'semester_id',
        'school_id'
    ];

    public function subjects()
    {
//        return $this->hasMany(ClassSubject::class, 'elective_subject_group_id');
        $classSubject = new ClassSubject();
        return $this->belongsToMany(Subject::class, ClassSubject::class, 'elective_subject_group_id', 'subject_id')->wherePivot('type', 'Elective')->withPivot('id as class_subject_id')->where($classSubject->getTable() . '.deleted_at',null)->withTrashed();
    }

    public function scopeOwner($query)
    {   
        if(Auth::user()) {
            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }
    
            if (Auth::user()->hasRole('School Admin')) {
                return $query->where('school_id', Auth::user()->school_id);
            }
    
            if (Auth::user()->hasRole('Student')) {
                return $query->where('school_id', Auth::user()->school_id);
            }
        }

        return $query;
    }
}
