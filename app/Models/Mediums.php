<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;


use App\Traits\TenantModel;


class Mediums extends Model
{
    use SoftDeletes, HasFactory, TenantModel;
    protected $fillable = ['name', 'school_id'];
    protected $hidden = ['created_at','updated_at'];
    // protected $connection = 'school';


    public function scopeOwner($query)
    {
        // if (Auth::user()->hasRole('Guardian')) {
        //     return $query->where('school_id', Auth::user()->school_id);
        // }
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
