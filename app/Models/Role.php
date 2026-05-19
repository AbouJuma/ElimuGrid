<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Traits\TenantModel;
use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole {
    use HasFactory, TenantModel;

    public function session_years_trackings()
    {
        return $this->hasMany(SessionYearsTracking::class, 'modal_id', 'id')->where('modal_type', 'App\Models\Role');
    }
}
