<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FixHostelFeatureAccessSeeder extends Seeder
{
    public function run()
    {
        echo "=== Fixing Hostel Management Feature Access ===\n\n";
        
        // 1. Get Hostel Management feature from main database
        $hostelFeature = DB::connection('mysql')->table('features')
            ->where('name', 'Hostel Management')
            ->first();
        
        if (!$hostelFeature) {
            echo "ERROR: Hostel Management feature not found in main database!\n";
            echo "Please run: php artisan db:seed --class=HostelManagementSeeder\n";
            return;
        }
        
        echo "Found Hostel Management feature (ID: {$hostelFeature->id})\n";
        
        // 2. Get all packages that should have this feature
        $packages = DB::connection('mysql')->table('packages')
            ->whereIn('name', ['Starter', 'Basic', 'Premium'])
            ->get();
        
        echo "Found " . $packages->count() . " packages to update\n";
        
        foreach ($packages as $package) {
            // Check if feature is linked to package
            $exists = DB::connection('mysql')->table('packages_features')
                ->where('package_id', $package->id)
                ->where('feature_id', $hostelFeature->id)
                ->first();
            
            if (!$exists) {
                DB::connection('mysql')->table('packages_features')->insert([
                    'package_id' => $package->id,
                    'feature_id' => $hostelFeature->id,
                ]);
                echo "  Added Hostel Management to package: {$package->name}\n";
            } else {
                echo "  Hostel Management already in package: {$package->name}\n";
            }
        }
        
        // 3. Get all schools and clear their feature cache
        $schools = DB::connection('mysql')->table('schools')->get();
        echo "\nFound " . $schools->count() . " schools\n";
        
        foreach ($schools as $school) {
            if (!$school->database_name) {
                continue;
            }
            
            echo "\nProcessing school: {$school->name}\n";
            
            try {
                // Clear the features cache for this school
                $cacheKey = 'features_' . $school->id;
                Cache::forget($cacheKey);
                echo "  Cleared cache: {$cacheKey}\n";
                
                // Also clear via school connection
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                
                // Check if school has permissions table
                $hasPermissionsTable = DB::connection('school')->getSchemaBuilder()->hasTable('permissions');
                if ($hasPermissionsTable) {
                    // Ensure permissions exist
                    $permissions = [
                        'hostel-list', 'hostel-create', 'hostel-edit', 'hostel-delete',
                        'room-list', 'room-create', 'room-edit', 'room-delete',
                        'hostel-allocation-list', 'hostel-allocation-create', 
                        'hostel-allocation-delete', 'hostel-report-view'
                    ];
                    
                    foreach ($permissions as $permName) {
                        $exists = DB::connection('school')->table('permissions')
                            ->where('name', $permName)
                            ->first();
                        if (!$exists) {
                            DB::connection('school')->table('permissions')->insert([
                                'name' => $permName,
                                'guard_name' => 'web',
                            ]);
                            echo "  Added permission: {$permName}\n";
                        }
                    }
                    
                    // Assign to School Admin role
                    $schoolAdminRole = DB::connection('school')->table('roles')
                        ->where('name', 'School Admin')
                        ->first();
                    
                    if ($schoolAdminRole) {
                        $assigned = 0;
                        foreach ($permissions as $permName) {
                            $perm = DB::connection('school')->table('permissions')
                                ->where('name', $permName)->first();
                            if ($perm) {
                                $hasPerm = DB::connection('school')->table('role_has_permissions')
                                    ->where('role_id', $schoolAdminRole->id)
                                    ->where('permission_id', $perm->id)
                                    ->first();
                                if (!$hasPerm) {
                                    DB::connection('school')->table('role_has_permissions')->insert([
                                        'role_id' => $schoolAdminRole->id,
                                        'permission_id' => $perm->id,
                                    ]);
                                    $assigned++;
                                }
                            }
                        }
                        echo "  Assigned {$assigned} permissions to School Admin\n";
                    }
                }
                
                // Clear cache again
                Cache::flush();
                
            } catch (\Exception $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
            }
        }
        
        // Reset to main database
        DB::setDefaultConnection('mysql');
        Config::set('database.connections.school.database', '');
        DB::purge('school');
        DB::connection('mysql')->reconnect();
        
        // Clear all caches
        Cache::flush();
        
        echo "\n=== Done! ===\n";
        echo "All caches cleared. Please refresh your browser.\n";
        echo "\nIf the menu is still locked, please verify:\n";
        echo "1. The school's subscription is active\n";
        echo "2. The school's package includes 'Hostel Management' feature\n";
        echo "3. The user has 'hostel-list' permission\n";
    }
}
