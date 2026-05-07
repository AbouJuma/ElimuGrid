<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Verifying User Permissions ===\n";

try {
    // Test specific user
    $testUser = DB::connection('mysql')->table('users')
        ->where('email', 'aboukisiaki@gmail.com')
        ->first();
    
    if (!$testUser) {
        echo "Test user not found\n";
        exit;
    }
    
    echo "Testing user: {$testUser->email} (School: {$testUser->school_id})\n\n";
    
    // Check main database permissions
    echo "=== Main Database Permissions ===\n";
    
    $mainDBPerms = DB::connection('mysql')->table('permissions')
        ->whereIn('id', function($query) use ($testUser) {
            $query->select('permission_id')
                ->from('model_has_permissions')
                ->where('model_id', $testUser->id)
                ->where('model_type', 'App\Models\User');
        })
        ->orWhereIn('id', function($query) use ($testUser) {
            $query->select('permission_id')
                ->from('role_has_permissions')
                ->whereIn('role_id', function($roleQuery) use ($testUser) {
                    $roleQuery->select('role_id')
                        ->from('model_has_roles')
                        ->where('model_id', $testUser->id)
                        ->where('model_type', 'App\Models\User');
                });
        })
        ->select('name')
        ->get();
    
    echo "Main DB permissions (" . count($mainDBPerms) . "):\n";
    foreach ($mainDBPerms as $perm) {
        if (strpos($perm->name, 'attendance') !== false) {
            echo "  ✓ {$perm->name}\n";
        }
    }
    
    // Check tenant database permissions
    echo "\n=== Tenant Database Permissions ===\n";
    
    $tenantDB = "eschool_saas_3_baobab";
    
    try {
        $tenantUser = DB::connection('mysql')->table("$tenantDB.users")
            ->where('email', $testUser->email)
            ->first();
        
        if ($tenantUser) {
            echo "Found user in tenant DB (ID: {$tenantUser->id})\n";
            
            $tenantPerms = DB::connection('mysql')->table("$tenantDB.permissions")
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
            
            echo "Tenant DB permissions (" . count($tenantPerms) . "):\n";
            foreach ($tenantPerms as $perm) {
                if (strpos($perm->name, 'attendance') !== false) {
                    echo "  ✓ {$perm->name}\n";
                }
            }
            
            // Check if attendance permissions exist in tenant
            echo "\n=== Attendance Permissions in Tenant ===\n";
            $attendancePerms = DB::connection('mysql')->table("$tenantDB.permissions")
                ->where('name', 'like', 'attendance%')
                ->get();
            
            foreach ($attendancePerms as $perm) {
                echo "- {$perm->name} (ID: {$perm->id})\n";
            }
            
            // Check user roles in tenant
            echo "\n=== User Roles in Tenant ===\n";
            $tenantRoles = DB::connection('mysql')->table("$tenantDB.model_has_roles")
                ->join("$tenantDB.roles", "$tenantDB.roles.id", '=', "$tenantDB.model_has_roles.role_id")
                ->where("$tenantDB.model_has_roles.model_id", $tenantUser->id)
                ->where("$tenantDB.model_has_roles.model_type", 'App\Models\User')
                ->select("$tenantDB.roles.name")
                ->get();
            
            foreach ($tenantRoles as $role) {
                echo "- {$role->name}\n";
            }
            
        } else {
            echo "User not found in tenant database\n";
        }
        
    } catch (Exception $e) {
        echo "Error accessing tenant DB: " . $e->getMessage() . "\n";
    }
    
    // Test another user
    echo "\n\n=== Testing Another User ===\n";
    $testUser2 = DB::connection('mysql')->table('users')
        ->where('email', 'kisiakiabou@gmail.com')
        ->first();
    
    if ($testUser2) {
        echo "Testing user: {$testUser2->email} (School: {$testUser2->school_id})\n";
        
        $tenantDB2 = "eschool_saas_1_shekilango";
        
        try {
            $tenantUser2 = DB::connection('mysql')->table("$tenantDB2.users")
                ->where('email', $testUser2->email)
                ->first();
            
            if ($tenantUser2) {
                $tenantPerms2 = DB::connection('mysql')->table("$tenantDB2.permissions")
                    ->whereIn('id', function($query) use ($tenantUser2) {
                        $query->select('permission_id')
                            ->from("$tenantDB2.model_has_permissions")
                            ->where('model_id', $tenantUser2->id)
                            ->where('model_type', 'App\Models\User');
                    })
                    ->orWhereIn('id', function($query) use ($tenantUser2) {
                        $query->select('permission_id')
                            ->from("$tenantDB2.role_has_permissions")
                            ->whereIn('role_id', function($roleQuery) use ($tenantUser2) {
                                $roleQuery->select('role_id')
                                    ->from("$tenantDB2.model_has_roles")
                                    ->where('model_id', $tenantUser2->id)
                                    ->where('model_type', 'App\Models\User');
                            });
                    })
                    ->select('name')
                    ->get();
                
                echo "Permissions (" . count($tenantPerms2) . "):\n";
                foreach ($tenantPerms2 as $perm) {
                    if (strpos($perm->name, 'attendance') !== false) {
                        echo "  ✓ {$perm->name}\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
