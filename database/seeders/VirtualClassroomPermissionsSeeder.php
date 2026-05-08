<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VirtualClassroomPermissionsSeeder extends Seeder
{
    public function run()
    {
        $virtualClassroomPermissions = [
            // Admin & Teacher permissions
            'virtual-classroom-list',
            'virtual-classroom-create',
            'virtual-classroom-edit',
            'virtual-classroom-delete',
            'virtual-classroom-join',
            'virtual-classroom-report-view',
        ];

        // Create permissions
        foreach ($virtualClassroomPermissions as $permissionName) {
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
            foreach ($virtualClassroomPermissions as $permissionName) {
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

        // Assign to School Admin role
        $schoolAdminRoleNames = ['School Admin', 'SchoolAdmin', 'school_admin', 'School_Admin', 'Administrator'];
        foreach ($schoolAdminRoleNames as $roleName) {
            $schoolAdminRole = DB::table('roles')->where('name', $roleName)->first();
            if ($schoolAdminRole) {
                foreach ($virtualClassroomPermissions as $permissionName) {
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
                echo "Virtual Classroom permissions assigned to role: {$roleName}\n";
                break;
            }
        }

        // Assign to Teacher role - limited permissions
        $teacherRole = DB::table('roles')->where('name', 'Teacher')->first();
        if ($teacherRole) {
            $teacherPermissions = [
                'virtual-classroom-list',
                'virtual-classroom-create',
                'virtual-classroom-edit',
                'virtual-classroom-join',
            ];
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
            echo "Virtual Classroom permissions assigned to Teacher role\n";
        }

        // Assign to Student role - only join permission
        $studentRole = DB::table('roles')->where('name', 'Student')->first();
        if ($studentRole) {
            $studentPermission = DB::table('permissions')->where('name', 'virtual-classroom-join')->first();
            if ($studentPermission) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $studentRole->id)
                    ->where('permission_id', $studentPermission->id)
                    ->first();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $studentRole->id,
                        'permission_id' => $studentPermission->id,
                    ]);
                }
            }
            echo "Virtual Classroom join permission assigned to Student role\n";
        }

        // Add Virtual Classroom feature to all existing packages
        $virtualClassroomFeature = DB::table('features')->where('name', 'Virtual Classroom')->first();
        if ($virtualClassroomFeature) {
            $packages = DB::table('packages')->get();
            foreach ($packages as $package) {
                $exists = DB::table('package_features')
                    ->where('package_id', $package->id)
                    ->where('feature_id', $virtualClassroomFeature->id)
                    ->first();
                if (!$exists) {
                    DB::table('package_features')->insert([
                        'package_id' => $package->id,
                        'feature_id' => $virtualClassroomFeature->id,
                    ]);
                    echo "Virtual Classroom added to package: {$package->name}\n";
                }
            }
        }

        // Mark migrations as run
        $now = now();
        $migrations = [
            '2025_05_07_300000_add_virtual_classroom_feature',
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
