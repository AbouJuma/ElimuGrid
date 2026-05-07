<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HostelManagementSeeder extends Seeder
{
    public function run()
    {
        // 1. Add Hostel Management Feature
        $hostelFeature = DB::table('features')->where('name', 'Hostel Management')->first();
        if (!$hostelFeature) {
            $featureId = DB::table('features')->insertGetId([
                'name' => 'Hostel Management',
                'is_default' => 0,
                'status' => 1,
                'required_vps' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            echo "Hostel Management feature created\n";
        } else {
            $featureId = $hostelFeature->id;
            echo "Hostel Management feature already exists\n";
        }

        // 2. Create Hostel Permissions
        $hostelPermissions = [
            'hostel-list',
            'hostel-create',
            'hostel-edit',
            'hostel-delete',
            'room-list',
            'room-create',
            'room-edit',
            'room-delete',
            'hostel-allocation-list',
            'hostel-allocation-create',
            'hostel-allocation-edit',
            'hostel-allocation-delete',
            'hostel-report-view',
        ];

        foreach ($hostelPermissions as $permissionName) {
            $exists = DB::table('permissions')->where('name', $permissionName)->first();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                echo "Permission created: {$permissionName}\n";
            }
        }

        // 3. Assign to Super Admin
        $superAdminRole = DB::table('roles')->where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            foreach ($hostelPermissions as $permissionName) {
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
            echo "Hostel permissions assigned to Super Admin\n";
        }

        // 4. Assign to School Admin role
        $schoolAdminRoleNames = ['School Admin', 'SchoolAdmin', 'school_admin', 'School_Admin', 'Administrator'];
        foreach ($schoolAdminRoleNames as $roleName) {
            $schoolAdminRole = DB::table('roles')->where('name', $roleName)->first();
            if ($schoolAdminRole) {
                foreach ($hostelPermissions as $permissionName) {
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
                echo "Hostel permissions assigned to role: {$roleName}\n";
                break;
            }
        }

        // 5. Add Hostel Management feature to all existing packages
        if ($featureId) {
            $packages = DB::table('packages')->get();
            foreach ($packages as $package) {
                $exists = DB::table('package_features')
                    ->where('package_id', $package->id)
                    ->where('feature_id', $featureId)
                    ->first();
                if (!$exists) {
                    DB::table('package_features')->insert([
                        'package_id' => $package->id,
                        'feature_id' => $featureId,
                    ]);
                    echo "Hostel Management added to package: {$package->name}\n";
                }
            }
        }

        // 6. Mark migrations as run
        $now = now();
        $migrations = [
            '2025_05_11_200000_add_hostel_management_feature',
            '2025_05_11_200001_add_hostel_permissions',
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

        echo "Hostel Management Module setup completed!\n";
    }
}
