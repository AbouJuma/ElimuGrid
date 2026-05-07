<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Giving Full Super Admin Permissions ===\n";

try {
    // Get super admin user
    $superAdmin = DB::connection('mysql')->table('users')
        ->where('email', 'superadmin@gmail.com')
        ->first();
    
    if (!$superAdmin) {
        echo "Super admin user not found.\n";
        exit;
    }
    
    echo "Processing super admin: {$superAdmin->first_name} {$superAdmin->last_name} (ID: {$superAdmin->id})\n";
    
    // Get ALL permissions from the database
    $allPermissions = DB::connection('mysql')->table('permissions')->get();
    echo "Found " . count($allPermissions) . " total permissions in system\n";
    
    // Remove any existing permissions for this user
    DB::connection('mysql')->table('model_has_permissions')
        ->where('model_id', $superAdmin->id)
        ->where('model_type', 'App\Models\User')
        ->delete();
    
    echo "✓ Cleared existing permissions\n";
    
    // Assign ALL permissions to super admin
    $assignedCount = 0;
    foreach ($allPermissions as $permission) {
        DB::connection('mysql')->table('model_has_permissions')->insert([
            'permission_id' => $permission->id,
            'model_id' => $superAdmin->id,
            'model_type' => 'App\Models\User',
        ]);
        $assignedCount++;
    }
    
    echo "✓ Assigned all $assignedCount permissions to super admin\n";
    
    // Also ensure Super Admin role exists and has all permissions
    $superAdminRole = DB::connection('mysql')->table('roles')
        ->where('name', 'Super Admin')
        ->first();
    
    if (!$superAdminRole) {
        echo "Creating Super Admin role...\n";
        $superAdminRoleId = DB::connection('mysql')->table('roles')->insertGetId([
            'name' => 'Super Admin',
            'custom_role' => 0,
            'editable' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } else {
        $superAdminRoleId = $superAdminRole->id;
    }
    
    // Assign Super Admin role to user
    DB::connection('mysql')->table('model_has_roles')
        ->updateOrInsert(
            ['model_id' => $superAdmin->id, 'model_type' => 'App\Models\User'],
            ['role_id' => $superAdminRoleId]
        );
    
    echo "✓ Assigned Super Admin role\n";
    
    // Give Super Admin role all permissions
    DB::connection('mysql')->table('role_has_permissions')
        ->where('role_id', $superAdminRoleId)
        ->delete();
    
    foreach ($allPermissions as $permission) {
        DB::connection('mysql')->table('role_has_permissions')->insert([
            'role_id' => $superAdminRoleId,
            'permission_id' => $permission->id,
        ]);
    }
    
    echo "✓ Gave Super Admin role all permissions\n";
    
    // Show what permissions were assigned
    echo "\n=== Categories of Permissions Assigned ===\n";
    $categories = [];
    foreach ($allPermissions as $permission) {
        $parts = explode('-', $permission->name);
        $category = $parts[0] ?? 'other';
        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }
        $categories[$category][] = $permission->name;
    }
    
    foreach ($categories as $category => $perms) {
        echo "📁 $category: " . count($perms) . " permissions\n";
        if (in_array($category, ['school', 'system', 'web', 'student', 'permission', 'feature', 'package'])) {
            foreach ($perms as $perm) {
                echo "   ✓ $perm\n";
            }
        }
    }
    
    echo "\n=== Full Access Granted ===\n";
    echo "✓ Super admin now has complete system access\n";
    echo "✓ Can access: School Management, Permissions, Web Settings, Students, Features, Packages, System Settings\n";
    echo "✓ All " . count($allPermissions) . " system permissions assigned\n";
    echo "\nThe super admin user (superadmin@gmail.com) now has full control over the entire system.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
