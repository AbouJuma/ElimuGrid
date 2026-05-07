<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransportManagementSeeder extends Seeder
{
    public function run()
    {
        echo "=== Registering Transport Management Module ===\n";

        // 1. Register Feature in main database
        $feature = DB::connection('mysql')->table('features')->where('name', 'Transport Management')->first();
        
        if (!$feature) {
            $featureId = DB::connection('mysql')->table('features')->insertGetId([
                'name' => 'Transport Management',
                'is_default' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            echo "✓ Created feature: Transport Management (ID: $featureId)\n";
        } else {
            $featureId = $feature->id;
            echo "ℹ Feature already exists: Transport Management (ID: $featureId)\n";
        }

        // 2. Define all Transport permissions
        $permissions = [
            // Routes
            ['name' => 'transport-route-list', 'description' => 'View transport routes'],
            ['name' => 'transport-route-create', 'description' => 'Create transport routes'],
            ['name' => 'transport-route-edit', 'description' => 'Edit transport routes'],
            ['name' => 'transport-route-delete', 'description' => 'Delete transport routes'],
            
            // Vehicles
            ['name' => 'transport-vehicle-list', 'description' => 'View transport vehicles'],
            ['name' => 'transport-vehicle-create', 'description' => 'Create transport vehicles'],
            ['name' => 'transport-vehicle-edit', 'description' => 'Edit transport vehicles'],
            ['name' => 'transport-vehicle-delete', 'description' => 'Delete transport vehicles'],
            
            // Drivers
            ['name' => 'transport-driver-list', 'description' => 'View transport drivers'],
            ['name' => 'transport-driver-create', 'description' => 'Create transport drivers'],
            ['name' => 'transport-driver-edit', 'description' => 'Edit transport drivers'],
            ['name' => 'transport-driver-delete', 'description' => 'Delete transport drivers'],
            
            // Stops
            ['name' => 'transport-stop-list', 'description' => 'View transport stops'],
            ['name' => 'transport-stop-create', 'description' => 'Create transport stops'],
            ['name' => 'transport-stop-edit', 'description' => 'Edit transport stops'],
            ['name' => 'transport-stop-delete', 'description' => 'Delete transport stops'],
            
            // Allocations
            ['name' => 'transport-allocation-list', 'description' => 'View transport allocations'],
            ['name' => 'transport-allocation-create', 'description' => 'Create transport allocations'],
            ['name' => 'transport-allocation-edit', 'description' => 'Edit transport allocations'],
            ['name' => 'transport-allocation-delete', 'description' => 'Delete transport allocations'],
            
            // Fees
            ['name' => 'transport-fee-list', 'description' => 'View transport fees'],
            ['name' => 'transport-fee-create', 'description' => 'Create transport fees'],
            ['name' => 'transport-fee-edit', 'description' => 'Edit transport fees'],
            ['name' => 'transport-fee-delete', 'description' => 'Delete transport fees'],
            ['name' => 'transport-fee-generate', 'description' => 'Generate transport fees manually'],
            
            // Reports
            ['name' => 'transport-report-view', 'description' => 'View transport reports'],
        ];

        // 3. Register permissions in main database
        foreach ($permissions as $perm) {
            $exists = DB::connection('mysql')->table('permissions')
                ->where('name', $perm['name'])
                ->first();

            if (!$exists) {
                DB::connection('mysql')->table('permissions')->insert([
                    'name' => $perm['name'],
                    'guard_name' => 'web',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                echo "✓ Created permission: {$perm['name']}\n";
            } else {
                echo "ℹ Permission exists: {$perm['name']}\n";
            }
        }

        // 4. Assign feature to packages (Starter, Basic, Premium)
        $packages = DB::connection('mysql')->table('packages')
            ->whereIn('name', ['Starter', 'Basic', 'Premium'])
            ->get();

        foreach ($packages as $package) {
            $exists = DB::connection('mysql')->table('package_features')
                ->where('package_id', $package->id)
                ->where('feature_id', $featureId)
                ->first();

            if (!$exists) {
                DB::connection('mysql')->table('package_features')->insert([
                    'package_id' => $package->id,
                    'feature_id' => $featureId,
                ]);
                echo "✓ Added Transport Management to package: {$package->name}\n";
            } else {
                echo "ℹ Transport Management already in package: {$package->name}\n";
            }
        }

        echo "\n=== Main Database Setup Complete ===\n";
    }
}
