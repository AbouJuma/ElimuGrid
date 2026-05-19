<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;


use App\Traits\TenantModel;


class Section extends Model
{
    use SoftDeletes, TenantModel;
    use HasFactory;

    protected $fillable = ['name', 'school_id'];
    protected $hidden = ['created_at','updated_at'];

    public function classes()
    {
        return $this->belongsToMany(ClassSchool::class, 'class_sections', 'section_id', 'class_id')->withTrashed();
    }

    public function class_sections()
    {
        return $this->hasMany(ClassSection::class, 'section_id');
    }

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            if (Auth::user()->school_id) {
                if (Auth::user()->hasRole('School Admin')) {
                    return $query->where('school_id', Auth::user()->school_id);
                }
    
                if (Auth::user()->hasRole('Student')) {
                    return $query->where('school_id', Auth::user()->school_id);
                }
                return $query->where('school_id', Auth::user()->school_id);
            }
            if (!Auth::user()->school_id) {
                if (Auth::user()->hasRole('Super Admin')) {
                    return $query;
                }
                return $query;
            }
        }

        return $query;
    }
}
