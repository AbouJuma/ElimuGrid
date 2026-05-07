<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fixing Tenant Database Permissions ===\n";

try {
    // Get actual tenant database names
    $tenantDatabases = [
        1 => 'eschool_saas_1_shekilango',
        2 => 'eschool_saas_2_demo',
        3 => 'eschool_saas_3_baobab',
        5 => 'eschool_saas_5_test',
        6 => 'eschool_saas_6_ist',
        7 => 'eschool_saas_7_ist',
        8 => 'eschool_saas_8_kilakala'
    ];
    
    // Get users with their school IDs
    $users = DB::connection('mysql')->table('users')
        ->where('school_id', '!=', null)
        ->whereIn('school_id', array_keys($tenantDatabases))
        ->get();
    
    foreach ($users as $user) {
        $tenantDB = $tenantDatabases[$user->school_id] ?? null;
        
        if (!$tenantDB) {
            echo "Skipping user {$user->email} - no tenant DB found for school {$user->school_id}\n";
            continue;
        }
        
        echo "\nProcessing user: {$user->email} (School: {$user->school_id}, DB: $tenantDB)\n";
        
        // Get user's roles from main database
        $userRoles = DB::connection('mysql')->table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->select('roles.name', 'roles.id', 'roles.custom_role', 'roles.editable')
            ->get();
        
        foreach ($userRoles as $role) {
            echo "  Role: {$role->name}\n";
            
            try {
                // Check if tenant database has required tables
                $tablesExist = DB::select("SHOW TABLES FROM `$tenantDB` LIKE 'permissions'");
                if (empty($tablesExist)) {
                    echo "    ✗ Tenant DB missing permissions table\n";
                    continue;
                }
                
                // Ensure role exists in tenant database
                $tenantRole = DB::connection('mysql')->table("$tenantDB.roles")
                    ->where('name', $role->name)
                    ->first();
                
                if (!$tenantRole) {
                    // Create role in tenant database
                    $tenantRoleId = DB::connection('mysql')->table("$tenantDB.roles")->insertGetId([
                        'name' => $role->name,
                        'custom_role' => $role->custom_role,
                        'editable' => $role->editable,
                        'school_id' => $user->school_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    echo "    ✓ Created role in tenant DB\n";
                } else {
                    $tenantRoleId = $tenantRole->id;
                    echo "    ✓ Role exists in tenant DB\n";
                }
                
                // Get permissions for this role from main database
                $rolePermissions = DB::connection('mysql')->table('role_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('role_has_permissions.role_id', $role->id)
                    ->select('permissions.name', 'permissions.id', 'permissions.guard_name')
                    ->get();
                
                echo "    Found " . count($rolePermissions) . " permissions\n";
                
                // Ensure permissions exist in tenant database
                foreach ($rolePermissions as $permission) {
                    $tenantPermission = DB::connection('mysql')->table("$tenantDB.permissions")
                        ->where('name', $permission->name)
                        ->first();
                    
                    if (!$tenantPermission) {
                        $tenantPermissionId = DB::connection('mysql')->table("$tenantDB.permissions")->insertGetId([
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name ?? 'web',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        echo "      ✓ Created permission: {$permission->name}\n";
                    } else {
                        $tenantPermissionId = $tenantPermission->id;
                    }
                    
                    // Assign permission to role in tenant database
                    $alreadyAssigned = DB::connection('mysql')->table("$tenantDB.role_has_permissions")
                        ->where('role_id', $tenantRoleId)
                        ->where('permission_id', $tenantPermissionId)
                        ->exists();
                    
                    if (!$alreadyAssigned) {
                        DB::connection('mysql')->table("$tenantDB.role_has_permissions")->insert([
                            'role_id' => $tenantRoleId,
                            'permission_id' => $tenantPermissionId,
                        ]);
                        echo "        ✓ Assigned permission to role\n";
                    }
                }
                
                // Find user in tenant database
                $tenantUser = DB::connection('mysql')->table("$tenantDB.users")
                    ->where('email', $user->email)
                    ->first();
                
                if ($tenantUser) {
                    // Assign role to user in tenant database
                    $alreadyAssigned = DB::connection('mysql')->table("$tenantDB.model_has_roles")
                        ->where('model_id', $tenantUser->id)
                        ->where('model_type', 'App\Models\User')
                        ->where('role_id', $tenantRoleId)
                        ->exists();
                    
                    if (!$alreadyAssigned) {
                        DB::connection('mysql')->table("$tenantDB.model_has_roles")->insert([
                            'role_id' => $tenantRoleId,
                            'model_id' => $tenantUser->id,
                            'model_type' => 'App\Models\User',
                        ]);
                        echo "    ✓ Assigned role to user in tenant DB\n";
                    } else {
                        echo "    ✓ User already has role in tenant DB\n";
                    }
                } else {
                    echo "    ✗ User not found in tenant DB\n";
                }
                
            } catch (Exception $e) {
                echo "    Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== Verification ===\n";
    
    // Test one user to verify permissions are working
    $testUser = DB::connection('mysql')->table('users')
        ->where('email', 'aboukisiaki@gmail.com')
        ->first();
    
    if ($testUser) {
        $tenantDB = $tenantDatabases[$testUser->school_id] ?? null;
        if ($tenantDB) {
            echo "Testing user: {$testUser->email} in $tenantDB\n";
            
            $tenantUser = DB::connection('mysql')->table("$tenantDB.users")
                ->where('email', $testUser->email)
                ->first();
            
            if ($tenantUser) {
                $userPermissions = DB::connection('mysql')->table("$tenantDB.permissions")
                    ->whereIn('id', function($query) use ($tenantUser) {
                        $query->select('permission_id')
                            ->from("$tenantDB.model_has_permissions")
                            ->where('model_id', $tenantUser->id)
                            ->where('model_type', 'App\Models\User');
                    })
                    ->orWhereIn('id', function($query) use ($tenantUser) {
                        $query->select('permission_id')
                            ->from("$tenantDB.role_has_permissions")
                            ->whereIn('role_id', function($roleQuery) use ($tenantUser) {
                                $roleQuery->select('role_id')
                                    ->from("$tenantDB.model_has_roles")
                                    ->where('model_id', $tenantUser->id)
                                    ->where('model_type', 'App\Models\User');
                            });
                    })
                    ->select('name')
                    ->get();
                
                echo "User has " . count($userPermissions) . " permissions:\n";
                foreach ($userPermissions as $perm) {
                    if (strpos($perm->name, 'attendance') !== false) {
                        echo "  ✓ {$perm->name}\n";
                    }
                }
            }
        }
    }
    
    echo "\n=== Fix Complete ===\n";
    echo "✓ Tenant database permissions synchronized\n";
    echo "✓ Users should now see their assigned permissions in their schools\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
