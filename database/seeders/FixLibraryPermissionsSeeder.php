<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixLibraryPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Get all library permissions
        $libraryPermissions = DB::table('permissions')
            ->whereIn('name', ['book-list', 'book-create', 'book-update', 'book-delete', 
                              'book-issue-list', 'book-issue-create', 'book-issue-return', 'book-report-view'])
            ->pluck('id', 'name');
        
        if ($libraryPermissions->isEmpty()) {
            echo "No library permissions found!\n";
            return;
        }
        
        echo "Found " . $libraryPermissions->count() . " library permissions\n";
        
        // Get or create School Admin role
        $schoolAdminRole = DB::table('roles')->where('name', 'School Admin')->first();
        
        if (!$schoolAdminRole) {
            echo "School Admin role not found! Creating it...\n";
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'School Admin',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $schoolAdminRole = DB::table('roles')->where('id', $roleId)->first();
            echo "Created School Admin role with ID: {$roleId}\n";
        } else {
            echo "School Admin role found (ID: {$schoolAdminRole->id})\n";
        }
        
        echo "School Admin role ID: " . $schoolAdminRole->id . "\n";
        
        // Assign all library permissions to School Admin
        $assignedCount = 0;
        foreach ($libraryPermissions as $name => $permissionId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $schoolAdminRole->id)
                ->where('permission_id', $permissionId)
                ->first();
            
            if (!$exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $schoolAdminRole->id,
                    'permission_id' => $permissionId,
                ]);
                echo "Assigned: {$name}\n";
                $assignedCount++;
            }
        }
        
        echo "Total permissions assigned: {$assignedCount}\n";
        
        // Also assign to Teacher role (view and issue permissions only)
        $teacherRole = DB::table('roles')->where('name', 'Teacher')->first();
        if (!$teacherRole) {
            echo "Teacher role not found! Creating it...\n";
            $teacherRoleId = DB::table('roles')->insertGetId([
                'name' => 'Teacher',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $teacherRole = DB::table('roles')->where('id', $teacherRoleId)->first();
            echo "Created Teacher role with ID: {$teacherRoleId}\n";
        } else {
            echo "Teacher role found (ID: {$teacherRole->id})\n";
        }
        
        $teacherPermissions = ['book-list', 'book-issue-list', 'book-issue-create', 'book-issue-return'];
        $teacherAssignedCount = 0;
        foreach ($teacherPermissions as $permName) {
            $permission = DB::table('permissions')->where('name', $permName)->first();
            if ($permission) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $teacherRole->id)
                    ->where('permission_id', $permission->id)
                    ->first();
                
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $teacherRole->id,
                        'permission_id' => $permission->id,
                    ]);
                    echo "Assigned to Teacher: {$permName}\n";
                    $teacherAssignedCount++;
                }
            }
        }
        echo "Total Teacher permissions assigned: {$teacherAssignedCount}\n";
        
        // Also add Library feature to all packages
        $libraryFeature = DB::table('features')->where('name', 'Library Management')->first();
        if ($libraryFeature) {
            $packages = DB::table('packages')->get();
            foreach ($packages as $package) {
                $exists = DB::table('package_features')
                    ->where('package_id', $package->id)
                    ->where('feature_id', $libraryFeature->id)
                    ->first();
                
                if (!$exists) {
                    DB::table('package_features')->insert([
                        'package_id' => $package->id,
                        'feature_id' => $libraryFeature->id,
                    ]);
                    echo "Added Library to package: {$package->name}\n";
                }
            }
        }
    }
}
