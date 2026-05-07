<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Resetting Super Admin Permissions ===\n";

try {
    // Get super admin user
    $superAdmin = DB::connection('mysql')->table('users')
        ->where('email', 'superadmin@gmail.com')
        ->first();
    
    if (!$superAdmin) {
        echo "Super admin user not found.\n";
        exit;
    }
    
    echo "Found super admin: {$superAdmin->first_name} {$superAdmin->last_name} (ID: {$superAdmin->id})\n";
    
    // Show current permissions before reset
    $currentPermissions = DB::connection('mysql')->table('model_has_permissions')
        ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
        ->where('model_has_permissions.model_id', $superAdmin->id)
        ->where('model_has_permissions.model_type', 'App\Models\User')
        ->pluck('permissions.name')
        ->toArray();
    
    echo "Current permissions count: " . count($currentPermissions) . "\n";
    
    // Remove all current permissions
    DB::connection('mysql')->table('model_has_permissions')
        ->where('model_id', $superAdmin->id)
        ->where('model_type', 'App\Models\User')
        ->delete();
    
    echo "✓ Removed all custom permissions\n";
    
    // Get Super Admin role
    $superAdminRole = DB::connection('mysql')->table('roles')
        ->where('name', 'Super Admin')
        ->first();
    
    if (!$superAdminRole) {
        echo "Super Admin role not found. Creating it...\n";
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
    
    // Get default permissions for Super Admin from InstallationSeeder
    $defaultPermissions = [
        'dashboard-list',
        'student-list',
        'student-create',
        'student-edit',
        'student-delete',
        'class-list',
        'class-create',
        'class-edit',
        'class-delete',
        'section-list',
        'section-create',
        'section-edit',
        'section-delete',
        'subject-list',
        'subject-create',
        'subject-edit',
        'subject-delete',
        'teacher-list',
        'teacher-create',
        'teacher-edit',
        'teacher-delete',
        'parent-list',
        'parent-create',
        'parent-edit',
        'parent-delete',
        'staff-list',
        'staff-create',
        'staff-edit',
        'staff-delete',
        'role-list',
        'role-create',
        'role-edit',
        'role-delete',
        'user-list',
        'user-create',
        'user-edit',
        'user-delete',
        'settings-list',
        'settings-create',
        'settings-edit',
        'settings-delete',
        'system-setting-manage',
        'school-terms-condition',
        'subscription-view',
        'subscription-settings',
        'subscription-change-bills',
        'database-backup',
        'school-custom-field-list',
        'school-custom-field-create',
        'school-custom-field-edit',
        'school-custom-field-delete',
        'contact-inquiry-list',
        'language-list',
        'language-create',
        'language-edit',
        'language-delete',
        'app-settings',
        'email-setting-create',
        'privacy-policy',
        'terms-condition',
        'contact-us',
        'about-us',
        'fcm-setting-create',
        'faqs-list',
        'faqs-create',
        'faqs-edit',
        'faqs-delete',
        'fcm-setting-manage',
        'web-settings',
        'custom-school-email',
        'addons-list',
        'addons-create',
        'addons-edit',
        'addons-delete',
        'guidance-list',
        'guidance-create',
        'guidance-edit',
        'guidance-delete',
        'subscription-bill-payment'
    ];
    
    // Assign default permissions to Super Admin role
    foreach ($defaultPermissions as $permissionName) {
        $permission = DB::connection('mysql')->table('permissions')
            ->where('name', $permissionName)
            ->first();
            
        if ($permission) {
            $exists = DB::connection('mysql')->table('role_has_permissions')
                ->where('role_id', $superAdminRoleId)
                ->where('permission_id', $permission->id)
                ->first();
                
            if (!$exists) {
                DB::connection('mysql')->table('role_has_permissions')->insert([
                    'role_id' => $superAdminRoleId,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
    
    echo "✓ Assigned default permissions to Super Admin role\n";
    
    // Verify the reset
    $finalPermissions = DB::connection('mysql')->table('model_has_permissions')
        ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
        ->join('role_has_permissions', 'model_has_permissions.permission_id', '=', 'role_has_permissions.permission_id')
        ->where('model_has_permissions.model_id', $superAdmin->id)
        ->where('model_has_permissions.model_type', 'App\Models\User')
        ->where('role_has_permissions.role_id', $superAdminRoleId)
        ->pluck('permissions.name')
        ->toArray();
    
    echo "\n=== Reset Complete ===\n";
    echo "Super admin now has " . count($finalPermissions) . " default permissions\n";
    echo "✓ Removed all custom permissions\n";
    echo "✓ Restored to default Super Admin role and permissions\n";
    echo "\nThe super admin user (superadmin@gmail.com) has been reset to default settings.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
