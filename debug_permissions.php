<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debugging Permission Assignment Issue ===\n";

try {
    // Get all roles
    $roles = DB::connection('mysql')->table('roles')->get();
    echo "Found " . count($roles) . " roles:\n";
    
    foreach ($roles as $role) {
        echo "- {$role->name} (ID: {$role->id}, Custom: {$role->custom_role}, Editable: {$role->editable})\n";
        
        // Get permissions for this role
        $rolePermissions = DB::connection('mysql')->table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $role->id)
            ->select('permissions.name')
            ->get();
            
        echo "  Permissions: " . count($rolePermissions) . "\n";
        foreach ($rolePermissions as $perm) {
            echo "    - {$perm->name}\n";
        }
        echo "\n";
    }
    
    // Get users and their roles/permissions
    $users = DB::connection('mysql')->table('users')->take(5)->get();
    echo "\n=== Sample Users ===\n";
    
    foreach ($users as $user) {
        echo "\nUser: {$user->first_name} {$user->last_name} ({$user->email}) - School ID: {$user->school_id}\n";
        
        // Get user's roles
        $userRoles = DB::connection('mysql')->table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->select('roles.name', 'roles.id')
            ->get();
            
        echo "  Roles:\n";
        foreach ($userRoles as $role) {
            echo "    - {$role->name} (ID: {$role->id})\n";
        }
        
        // Get user's direct permissions
        $userPermissions = DB::connection('mysql')->table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('model_has_permissions.model_id', $user->id)
            ->where('model_has_permissions.model_type', 'App\Models\User')
            ->select('permissions.name')
            ->get();
            
        echo "  Direct Permissions:\n";
        foreach ($userPermissions as $perm) {
            echo "    - {$perm->name}\n";
        }
        
        // Get all effective permissions (through roles + direct)
        $effectivePermissions = DB::connection('mysql')->table('permissions')
            ->where(function($query) use ($user) {
                // Direct permissions
                $query->whereIn('id', function($subQuery) use ($user) {
                    $subQuery->select('permission_id')
                        ->from('model_has_permissions')
                        ->where('model_id', $user->id)
                        ->where('model_type', 'App\Models\User');
                })
                // Permissions through roles
                ->orWhereIn('id', function($subQuery) use ($user) {
                    $subQuery->select('permission_id')
                        ->from('role_has_permissions')
                        ->whereIn('role_id', function($roleQuery) use ($user) {
                            $roleQuery->select('role_id')
                                ->from('model_has_roles')
                                ->where('model_id', $user->id)
                                ->where('model_type', 'App\Models\User');
                        });
                });
            })
            ->select('name')
            ->get();
            
        echo "  Effective Permissions (Total: " . count($effectivePermissions) . "):\n";
        foreach ($effectivePermissions as $perm) {
            echo "    - {$perm->name}\n";
        }
    }
    
    // Check specific attendance permissions
    echo "\n=== Attendance Permissions Check ===\n";
    $attendancePerms = DB::connection('mysql')->table('permissions')
        ->where('name', 'like', 'attendance%')
        ->get();
    
    echo "Attendance permissions in system:\n";
    foreach ($attendancePerms as $perm) {
        echo "- {$perm->name} (ID: {$perm->id})\n";
        
        // Check which roles have this permission
        $rolesWithPerm = DB::connection('mysql')->table('role_has_permissions')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->where('role_has_permissions.permission_id', $perm->id)
            ->select('roles.name')
            ->get();
            
        echo "  Roles with this permission:\n";
        foreach ($rolesWithPerm as $role) {
            echo "    - {$role->name}\n";
        }
        
        // Check which users have this permission directly
        $usersWithPerm = DB::connection('mysql')->table('model_has_permissions')
            ->join('users', 'users.id', '=', 'model_has_permissions.model_id')
            ->where('model_has_permissions.permission_id', $perm->id)
            ->where('model_has_permissions.model_type', 'App\Models\User')
            ->select('users.email')
            ->get();
            
        if ($usersWithPerm->count() > 0) {
            echo "  Users with direct permission:\n";
            foreach ($usersWithPerm as $user) {
                echo "    - {$user->email}\n";
            }
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
