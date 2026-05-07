<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FixSchoolHostelPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Get all schools from the MAIN database
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Found " . $schools->count() . " schools\n";
        
        foreach ($schools as $school) {
            if (!$school->database_name) {
                echo "School {$school->name} has no database_name, skipping.\n";
                continue;
            }
            
            echo "\n=== Processing school: {$school->name} (DB: {$school->database_name}) ===\n";
            
            try {
                // Switch to school database
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                
                // Check if the school database has the permissions table
                $hasTable = DB::connection('school')->getSchemaBuilder()->hasTable('permissions');
                if (!$hasTable) {
                    echo "  No permissions table, skipping.\n";
                    continue;
                }
                
                // 1. Add hostel permissions
                $hostelPermissions = [
                    ['name' => 'hostel-list', 'guard_name' => 'web'],
                    ['name' => 'hostel-create', 'guard_name' => 'web'],
                    ['name' => 'hostel-edit', 'guard_name' => 'web'],
                    ['name' => 'hostel-delete', 'guard_name' => 'web'],
                    ['name' => 'room-list', 'guard_name' => 'web'],
                    ['name' => 'room-create', 'guard_name' => 'web'],
                    ['name' => 'room-edit', 'guard_name' => 'web'],
                    ['name' => 'room-delete', 'guard_name' => 'web'],
                    ['name' => 'hostel-allocation-list', 'guard_name' => 'web'],
                    ['name' => 'hostel-allocation-create', 'guard_name' => 'web'],
                    ['name' => 'hostel-allocation-edit', 'guard_name' => 'web'],
                    ['name' => 'hostel-allocation-delete', 'guard_name' => 'web'],
                    ['name' => 'hostel-report-view', 'guard_name' => 'web'],
                ];
                
                foreach ($hostelPermissions as $perm) {
                    $exists = DB::connection('school')->table('permissions')
                        ->where('name', $perm['name'])
                        ->first();
                    if (!$exists) {
                        DB::connection('school')->table('permissions')->insert($perm);
                        echo "  Added permission: {$perm['name']}\n";
                    }
                }
                
                // 2. Assign hostel permissions to School Admin role
                $schoolAdminRole = DB::connection('school')->table('roles')
                    ->where('name', 'School Admin')
                    ->first();
                
                if (!$schoolAdminRole) {
                    // Try to find any admin-like role
                    $schoolAdminRole = DB::connection('school')->table('roles')
                        ->where('name', 'like', '%Admin%')
                        ->orWhere('name', 'like', '%admin%')
                        ->first();
                    
                    if (!$schoolAdminRole) {
                        // Get the actual school_id from the school's own database
                        $schoolRecord = DB::connection('school')->table('schools')->first();
                        $schoolIdForRole = $schoolRecord ? $schoolRecord->id : null;
                        
                        // Create School Admin role
                        $roleData = [
                            'name' => 'School Admin',
                            'guard_name' => 'web',
                            'custom_role' => 0,
                            'editable' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        if ($schoolIdForRole) {
                            $roleData['school_id'] = $schoolIdForRole;
                        }
                        $roleId = DB::connection('school')->table('roles')->insertGetId($roleData);
                        $schoolAdminRole = DB::connection('school')->table('roles')->where('id', $roleId)->first();
                        echo "  Created School Admin role (ID: {$roleId})\n";
                        
                        // Assign this role to the school admin user
                        $adminUser = DB::connection('school')->table('users')
                            ->where('id', $school->admin_id)
                            ->first();
                        if ($adminUser) {
                            $hasRole = DB::connection('school')->table('model_has_roles')
                                ->where('model_id', $adminUser->id)
                                ->where('role_id', $schoolAdminRole->id)
                                ->first();
                            if (!$hasRole) {
                                DB::connection('school')->table('model_has_roles')->insert([
                                    'role_id' => $schoolAdminRole->id,
                                    'model_id' => $adminUser->id,
                                    'model_type' => 'App\Models\User',
                                ]);
                                echo "  Assigned School Admin role to user {$adminUser->email}\n";
                            }
                        }
                    } else {
                        echo "  Found admin role: {$schoolAdminRole->name} (ID: {$schoolAdminRole->id})\n";
                    }
                } else {
                    echo "  Found School Admin role (ID: {$schoolAdminRole->id})\n";
                }
                
                if ($schoolAdminRole) {
                    $adminPermNames = ['hostel-list', 'hostel-create', 'hostel-edit', 'hostel-delete', 
                                      'room-list', 'room-create', 'room-edit', 'room-delete',
                                      'hostel-allocation-list', 'hostel-allocation-create', 
                                      'hostel-allocation-delete', 'hostel-report-view'];
                    $assigned = 0;
                    foreach ($adminPermNames as $permName) {
                        $perm = DB::connection('school')->table('permissions')
                            ->where('name', $permName)->first();
                        if ($perm) {
                            $exists = DB::connection('school')->table('role_has_permissions')
                                ->where('role_id', $schoolAdminRole->id)
                                ->where('permission_id', $perm->id)
                                ->first();
                            if (!$exists) {
                                DB::connection('school')->table('role_has_permissions')->insert([
                                    'role_id' => $schoolAdminRole->id,
                                    'permission_id' => $perm->id,
                                ]);
                                $assigned++;
                            }
                        }
                    }
                    echo "  Assigned {$assigned} permissions to {$schoolAdminRole->name}\n";
                }
                
                // 3. Add 'Hostel Management' feature to school's features table if exists
                $hasFeaturesTable = DB::connection('school')->getSchemaBuilder()->hasTable('features');
                if ($hasFeaturesTable) {
                    $featureExists = DB::connection('school')->table('features')
                        ->where('name', 'Hostel Management')
                        ->first();
                    
                    if (!$featureExists) {
                        DB::connection('school')->table('features')->insert([
                            'name' => 'Hostel Management',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        echo "  Added Hostel Management feature to school\n";
                    }
                }
                
            } catch (\Exception $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
            }
        }
        
        // Switch back to main database
        DB::setDefaultConnection('mysql');
        Config::set('database.connections.school.database', '');
        DB::purge('school');
        DB::connection('mysql')->reconnect();
        DB::setDefaultConnection('mysql');
        
        echo "\n=== Done! ===\n";
        echo "Hostel permissions added to all school databases.\n";
        echo "Clear cache: php artisan view:clear\n";
    }
}
