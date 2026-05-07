<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class AssignStudentRolesSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Assigning Student roles in school databases...\n";
        
        foreach ($schools as $school) {
            if (!$school->database_name) {
                continue;
            }
            
            echo "\n=== Processing school: {$school->name} (DB: {$school->database_name}) ===\n";
            
            try {
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');
                
                // Get Student role
                $studentRole = DB::connection('school')->table('roles')
                    ->where('name', 'Student')
                    ->first();
                
                if (!$studentRole) {
                    // Create Student role
                    $roleId = DB::connection('school')->table('roles')->insertGetId([
                        'name' => 'Student',
                        'guard_name' => 'web',
                        'custom_role' => 0,
                        'editable' => 1,
                        'school_id' => $school->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $studentRole = DB::connection('school')->table('roles')->where('id', $roleId)->first();
                    echo "  Created Student role (ID: {$roleId})\n";
                } else {
                    echo "  Student role found (ID: {$studentRole->id})\n";
                }
                
                // Get all students and assign them the Student role
                $students = DB::connection('school')->table('students')->get();
                $assignedCount = 0;
                
                foreach ($students as $student) {
                    // Check if user exists
                    $user = DB::connection('school')->table('users')
                        ->where('id', $student->user_id)
                        ->first();
                    
                    if ($user) {
                        // Check if already has Student role
                        $hasRole = DB::connection('school')->table('model_has_roles')
                            ->where('role_id', $studentRole->id)
                            ->where('model_id', $user->id)
                            ->where('model_type', 'App\\Models\\User')
                            ->first();
                        
                        if (!$hasRole) {
                            DB::connection('school')->table('model_has_roles')->insert([
                                'role_id' => $studentRole->id,
                                'model_id' => $user->id,
                                'model_type' => 'App\\Models\\User',
                            ]);
                            $assignedCount++;
                            echo "  Assigned Student role to user {$user->email}\n";
                        }
                    } else {
                        echo "  Warning: User not found for student ID {$student->id}\n";
                    }
                }
                
                echo "  Assigned Student role to {$assignedCount} users\n";
                
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
