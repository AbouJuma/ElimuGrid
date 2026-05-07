<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixSchoolTransportPermissionsSeeder extends Seeder
{
    public function run()
    {
        echo "=== Setting Up Transport Management in Each School ===\n\n";

        // Get Transport Management feature
        $feature = DB::connection('mysql')->table('features')
            ->where('name', 'Transport Management')
            ->first();

        if (!$feature) {
            echo "ERROR: Transport Management feature not found! Run TransportManagementSeeder first.\n";
            return;
        }

        $featureId = $feature->id;

        // Get all permissions
        $permissionNames = [
            'transport-route-list', 'transport-route-create', 'transport-route-edit', 'transport-route-delete',
            'transport-vehicle-list', 'transport-vehicle-create', 'transport-vehicle-edit', 'transport-vehicle-delete',
            'transport-driver-list', 'transport-driver-create', 'transport-driver-edit', 'transport-driver-delete',
            'transport-stop-list', 'transport-stop-create', 'transport-stop-edit', 'transport-stop-delete',
            'transport-allocation-list', 'transport-allocation-create', 'transport-allocation-edit', 'transport-allocation-delete',
            'transport-fee-list', 'transport-fee-create', 'transport-fee-edit', 'transport-fee-delete', 'transport-fee-generate',
            'transport-report-view',
        ];

        $permissions = DB::connection('mysql')->table('permissions')
            ->whereIn('name', $permissionNames)
            ->get();

        echo "Found " . $permissions->count() . " permissions to assign\n";

        // Get all schools
        $schools = DB::connection('mysql')->table('schools')->get();
        echo "Processing " . $schools->count() . " schools\n\n";

        foreach ($schools as $school) {
            if (!$school->database_name) {
                echo "Skipping school {$school->name} - no database\n";
                continue;
            }

            echo "Processing: {$school->name} ({$school->database_name})\n";

            try {
                // Switch to school database
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::connection('school')->getPdo();

                // Check if tables exist
                $hasPermissionsTable = DB::connection('school')->getSchemaBuilder()->hasTable('permissions');
                if (!$hasPermissionsTable) {
                    echo "  ⚠ No permissions table\n";
                    continue;
                }

                // Add permissions
                $addedCount = 0;
                foreach ($permissions as $perm) {
                    $exists = DB::connection('school')->table('permissions')
                        ->where('name', $perm->name)
                        ->first();

                    if (!$exists) {
                        DB::connection('school')->table('permissions')->insert([
                            'name' => $perm->name,
                            'guard_name' => 'web',
                        ]);
                        $addedCount++;
                    }
                }
                echo "  ✓ Added {$addedCount} permissions\n";

                // Assign to School Admin role
                $schoolAdminRole = DB::connection('school')->table('roles')
                    ->where('name', 'School Admin')
                    ->first();

                if ($schoolAdminRole) {
                    $assignedCount = 0;
                    foreach ($permissions as $perm) {
                        $schoolPerm = DB::connection('school')->table('permissions')
                            ->where('name', $perm->name)
                            ->first();

                        if ($schoolPerm) {
                            $hasPerm = DB::connection('school')->table('role_has_permissions')
                                ->where('role_id', $schoolAdminRole->id)
                                ->where('permission_id', $schoolPerm->id)
                                ->first();

                            if (!$hasPerm) {
                                DB::connection('school')->table('role_has_permissions')->insert([
                                    'role_id' => $schoolAdminRole->id,
                                    'permission_id' => $schoolPerm->id,
                                ]);
                                $assignedCount++;
                            }
                        }
                    }
                    echo "  ✓ Assigned {$assignedCount} permissions to School Admin\n";
                }

                // Add feature flag to school
                $hasFeaturesTable = DB::connection('school')->getSchemaBuilder()->hasTable('features');
                if ($hasFeaturesTable) {
                    $exists = DB::connection('school')->table('features')
                        ->where('id', $featureId)
                        ->first();

                    if (!$exists) {
                        DB::connection('school')->table('features')->insert([
                            'id' => $featureId,
                            'name' => 'Transport Management',
                            'is_default' => 0,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        echo "  ✓ Added feature flag\n";
                    }
                }

            } catch (\Exception $e) {
                echo "  ✗ ERROR: " . $e->getMessage() . "\n";
            }
        }

        // Reset to main database
        DB::setDefaultConnection('mysql');
        Config::set('database.connections.school.database', '');
        DB::purge('school');
        DB::connection('mysql')->reconnect();

        echo "\n=== School Setup Complete ===\n";
    }
}
