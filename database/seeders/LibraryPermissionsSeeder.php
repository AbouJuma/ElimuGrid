<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LibraryPermissionsSeeder extends Seeder
{
    public function run()
    {
        $libraryPermissions = [
            'book-list',
            'book-create',
            'book-update',
            'book-delete',
            'book-issue-list',
            'book-issue-create',
            'book-issue-return',
            'book-report-view',
        ];

        // Create permissions
        foreach ($libraryPermissions as $permissionName) {
            $exists = DB::table('permissions')->where('name', $permissionName)->first();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        // Assign to Super Admin
        $superAdminRole = DB::table('roles')->where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            foreach ($libraryPermissions as $permissionName) {
                $permission = DB::table('permissions')->where('name', $permissionName)->first();
                if ($permission) {
                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $superAdminRole->id)
                        ->where('permission_id', $permission->id)
                        ->first();

                    if (!$exists) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $superAdminRole->id,
                            'permission_id' => $permission->id,
                        ]);
                    }
                }
            }
        }

        // Assign to School Admin role (check various possible names)
        $schoolAdminRoleNames = ['School Admin', 'SchoolAdmin', 'school_admin', 'School_Admin', 'Administrator'];
        foreach ($schoolAdminRoleNames as $roleName) {
            $schoolAdminRole = DB::table('roles')->where('name', $roleName)->first();
            if ($schoolAdminRole) {
                foreach ($libraryPermissions as $permissionName) {
                    $permission = DB::table('permissions')->where('name', $permissionName)->first();
                    if ($permission) {
                        $exists = DB::table('role_has_permissions')
                            ->where('role_id', $schoolAdminRole->id)
                            ->where('permission_id', $permission->id)
                            ->first();

                        if (!$exists) {
                            DB::table('role_has_permissions')->insert([
                                'role_id' => $schoolAdminRole->id,
                                'permission_id' => $permission->id,
                            ]);
                        }
                    }
                }
                echo "Library permissions assigned to role: {$roleName}\n";
                break; // Stop after finding and assigning to first matching role
            }
        }

        // Assign to Teacher role
        $teacherRole = DB::table('roles')->where('name', 'Teacher')->first();
        if ($teacherRole) {
            $teacherPermissions = ['book-list', 'book-issue-list', 'book-issue-create', 'book-issue-return'];
            foreach ($teacherPermissions as $permissionName) {
                $permission = DB::table('permissions')->where('name', $permissionName)->first();
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
                    }
                }
            }
        }

        // Add Library Management feature to all existing packages
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
                    echo "Library Management added to package: {$package->name}\n";
                }
            }
        }

        // Mark migrations as run
        $now = now();
        $migrations = [
            '2025_05_10_200000_add_library_management_feature',
            '2025_05_10_200001_add_library_permissions_to_role',
        ];

        foreach ($migrations as $migration) {
            $exists = DB::table('migrations')->where('migration', $migration)->first();
            if (!$exists) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => DB::table('migrations')->max('batch') + 1,
                ]);
            }
        }
    }
}
