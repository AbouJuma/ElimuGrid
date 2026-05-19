<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Traits\TenantModel;
use Spatie\Permission\Models\Permission as BasePermission;

class Permission extends BasePermission {
    use HasFactory, TenantModel;
}
