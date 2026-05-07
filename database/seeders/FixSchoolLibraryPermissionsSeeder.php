<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FixSchoolLibraryPermissionsSeeder extends Seeder
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
                
                // 1. Add library permissions
                $libraryPermissions = [
                    ['name' => 'book-list', 'guard_name' => 'web'],
                    ['name' => 'book-create', 'guard_name' => 'web'],
                    ['name' => 'book-edit', 'guard_name' => 'web'],
                    ['name' => 'book-delete', 'guard_name' => 'web'],
                    ['name' => 'book-issue-list', 'guard_name' => 'web'],
                    ['name' => 'book-issue-create', 'guard_name' => 'web'],
                    ['name' => 'book-issue-edit', 'guard_name' => 'web'],
                    ['name' => 'book-issue-delete', 'guard_name' => 'web'],
                    ['name' => 'book-issue-return', 'guard_name' => 'web'],
                    ['name' => 'book-report-view', 'guard_name' => 'web'],
                ];
                
                foreach ($libraryPermissions as $perm) {
                    $exists = DB::connection('school')->table('permissions')
                        ->where('name', $perm['name'])
                        ->first();
                    if (!$exists) {
                        DB::connection('school')->table('permissions')->insert($perm);
                        echo "  Added permission: {$perm['name']}\n";
                    }
                }
                
                // 2. Assign library permissions to School Admin role (or create it)
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
                }
                
                if ($schoolAdminRole) {
                    $adminPermNames = ['book-list', 'book-create', 'book-edit', 'book-delete', 
                                      'book-issue-list', 'book-issue-create', 'book-issue-return', 'book-report-view'];
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
                
                // 3. Assign limited library permissions to Teacher role
                $teacherRole = DB::connection('school')->table('roles')
                    ->where('name', 'Teacher')
                    ->first();
                
                if ($teacherRole) {
                    $teacherPermNames = ['book-list', 'book-issue-list', 'book-issue-create', 'book-issue-return'];
                    $assigned = 0;
                    foreach ($teacherPermNames as $permName) {
                        $perm = DB::connection('school')->table('permissions')
                            ->where('name', $permName)->first();
                        if ($perm) {
                            $exists = DB::connection('school')->table('role_has_permissions')
                                ->where('role_id', $teacherRole->id)
                                ->where('permission_id', $perm->id)
                                ->first();
                            if (!$exists) {
                                DB::connection('school')->table('role_has_permissions')->insert([
                                    'role_id' => $teacherRole->id,
                                    'permission_id' => $perm->id,
                                ]);
                                $assigned++;
                            }
                        }
                    }
                    echo "  Assigned {$assigned} permissions to Teacher\n";
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
    }
}
