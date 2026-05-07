<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fixing User Role Assignments ===\n";

try {
    // Get all users who should have roles but don't
    $usersWithoutRoles = DB::connection('mysql')->table('users')
        ->whereNotIn('id', function($query) {
            $query->select('model_id')
                ->from('model_has_roles')
                ->where('model_type', 'App\Models\User');
        })
        ->where('email', '!=', 'superadmin@gmail.com') // Skip super admin
        ->get();
    
    echo "Found " . count($usersWithoutRoles) . " users without roles:\n";
    
    foreach ($usersWithoutRoles as $user) {
        echo "\n- User: {$user->first_name} {$user->last_name} ({$user->email}) - School ID: {$user->school_id}\n";
        
        // Determine appropriate role based on user type/email
        $roleName = 'School Admin'; // Default role
        
        if (strpos($user->email, 'teacher') !== false) {
            $roleName = 'Teacher';
        } elseif (strpos($user->email, 'student') !== false) {
            $roleName = 'Student';
        } elseif (strpos($user->email, 'guardian') !== false) {
            $roleName = 'Guardian';
        } elseif ($user->school_id === null) {
            $roleName = 'Super Admin'; // For system-level users
        }
        
        // Get the role ID
        $role = DB::connection('mysql')->table('roles')
            ->where('name', $roleName)
            ->first();
            
        if ($role) {
            // Assign role to user
            DB::connection('mysql')->table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_id' => $user->id,
                'model_type' => 'App\Models\User',
            ]);
            
            echo "  ✓ Assigned role: {$roleName}\n";
        } else {
            echo "  ✗ Role '{$roleName}' not found\n";
        }
    }
    
    // Now check for users with roles but no permissions in their tenant database
    echo "\n=== Checking Tenant Database Permissions ===\n";
    
    $usersWithRoles = DB::connection('mysql')->table('users')
        ->whereIn('id', function($query) {
            $query->select('model_id')
                ->from('model_has_roles')
                ->where('model_type', 'App\Models\User');
        })
        ->where('school_id', '!=', null) // Only tenant users
        ->get();
    
    foreach ($usersWithRoles as $user) {
        echo "\nChecking user: {$user->email} (School: {$user->school_id})\n";
        
        // Get user's role and permissions from main database
        $userRoles = DB::connection('mysql')->table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->select('roles.name', 'roles.id')
            ->get();
        
        foreach ($userRoles as $role) {
            echo "  Role: {$role->name}\n";
            
            // Get permissions for this role
            $rolePermissions = DB::connection('mysql')->table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', $role->id)
                ->select('permissions.name')
                ->get();
            
            // Connect to tenant database
            $tenantDBName = "eschool_saas_{$user->school_id}_" . strtolower(str_replace(' ', '_', 'School_' . $user->school_id));
            
            try {
                // Check if tenant database exists and has permissions table
                $tenantTables = DB::select("SHOW TABLES FROM `$tenantDBName` LIKE 'permissions'");
                
                if (count($tenantTables) > 0) {
                    echo "    Tenant DB: $tenantDBName ✓\n";
                    
                    // Ensure permissions exist in tenant database
                    foreach ($rolePermissions as $permission) {
                        $permExists = DB::connection('mysql')->table($tenantDBName . '.permissions')
                            ->where('name', $permission->name)
                            ->exists();
                            
                        if (!$permExists) {
                            // Copy permission from main database to tenant
                            $mainPerm = DB::connection('mysql')->table('permissions')
                                ->where('name', $permission->name)
                                ->first();
                                
                            if ($mainPerm) {
                                DB::connection('mysql')->table($tenantDBName . '.permissions')->insert([
                                    'name' => $mainPerm->name,
                                    'guard_name' => $mainPerm->guard_name ?? 'web',
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                echo "      ✓ Added permission: {$permission->name}\n";
                            }
                        }
                    }
                    
                    // Ensure role exists in tenant database
                    $roleExists = DB::connection('mysql')->table($tenantDBName . '.roles')
                        ->where('name', $role->name)
                        ->exists();
                        
                    if (!$roleExists) {
                        $mainRole = DB::connection('mysql')->table('roles')
                            ->where('id', $role->id)
                            ->first();
                            
                        if ($mainRole) {
                            DB::connection('mysql')->table($tenantDBName . '.roles')->insert([
                                'name' => $mainRole->name,
                                'custom_role' => $mainRole->custom_role,
                                'editable' => $mainRole->editable,
                                'school_id' => $user->school_id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            echo "      ✓ Added role: {$role->name}\n";
                        }
                    }
                    
                    // Assign permissions to role in tenant database
                    $tenantRoleId = DB::connection('mysql')->table($tenantDBName . '.roles')
                        ->where('name', $role->name)
                        ->value('id');
                        
                    if ($tenantRoleId) {
                        foreach ($rolePermissions as $permission) {
                            $tenantPermId = DB::connection('mysql')->table($tenantDBName . '.permissions')
                                ->where('name', $permission->name)
                                ->value('id');
                                
                            if ($tenantPermId) {
                                $alreadyAssigned = DB::connection('mysql')->table($tenantDBName . '.role_has_permissions')
                                    ->where('role_id', $tenantRoleId)
                                    ->where('permission_id', $tenantPermId)
                                    ->exists();
                                    
                                if (!$alreadyAssigned) {
                                    DB::connection('mysql')->table($tenantDBName . '.role_has_permissions')->insert([
                                        'role_id' => $tenantRoleId,
                                        'permission_id' => $tenantPermId,
                                    ]);
                                    echo "        ✓ Assigned: {$permission->name}\n";
                                }
                            }
                        }
                    }
                    
                    // Assign role to user in tenant database
                    $tenantUserId = DB::connection('mysql')->table($tenantDBName . '.users')
                        ->where('email', $user->email)
                        ->value('id');
                        
                    if ($tenantUserId && $tenantRoleId) {
                        $alreadyAssigned = DB::connection('mysql')->table($tenantDBName . '.model_has_roles')
                            ->where('model_id', $tenantUserId)
                            ->where('model_type', 'App\Models\User')
                            ->where('role_id', $tenantRoleId)
                            ->exists();
                            
                        if (!$alreadyAssigned) {
                            DB::connection('mysql')->table($tenantDBName . '.model_has_roles')->insert([
                                'role_id' => $tenantRoleId,
                                'model_id' => $tenantUserId,
                                'model_type' => 'App\Models\User',
                            ]);
                            echo "      ✓ Assigned role to user in tenant DB\n";
                        }
                    }
                    
                } else {
                    echo "    Tenant DB: $tenantDBName ✗ (no permissions table)\n";
                }
                
            } catch (Exception $e) {
                echo "    Error accessing tenant DB: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== Fix Complete ===\n";
    echo "✓ User role assignments fixed\n";
    echo "✓ Tenant database permissions synchronized\n";
    echo "✓ Users should now see their assigned permissions\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
