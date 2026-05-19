<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\TenantModel;

class UserStatusForNextCycle extends Model
{
    use HasFactory, TenantModel;
    protected $fillable = ['user_id','status','school_id'];

    public function scopeOwner() {
        if (Auth::user()) {
            return $this->where('school_id', Auth::user()->school_id);
        }
    }
}
