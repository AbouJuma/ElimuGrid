<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FixStudentRoleAssignmentSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Fixing Student role assignments in school databases...\n";
        
        foreach ($schools as $school) {
            if (!$school->database_name || $school->id != 3) {
                continue; // Only fix Baobab school (ID 3)
            }
            
            echo "\n=== School: {$school->name} (DB: {$school->database_name}) ===\n";
            
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
                    echo "  Student role not found!\n";
                    continue;
                }
                
                echo "  Student role ID: {$studentRole->id}\n";
                
                // Get all students and ensure they have the Student role
                $students = DB::connection('school')->table('students')->get();
                $fixedCount = 0;
                
                foreach ($students as $student) {
                    // Check if user exists
                    $user = DB::connection('school')->table('users')
                        ->where('id', $student->user_id)
                        ->first();
                    
                    if ($user) {
                        // Check current role assignments
                        $currentRoles = DB::connection('school')->table('model_has_roles')
                            ->where('model_id', $user->id)
                            ->where('model_type', 'App\\Models\\User')
                            ->get();
                        
                        echo "  User {$user->email} (ID: {$user->id}) has roles: ";
                        foreach ($currentRoles as $role) {
                            $roleName = DB::connection('school')->table('roles')
                                ->where('id', $role->role_id)
                                ->value('name');
                            echo "{$roleName} ({$role->role_id}) ";
                        }
                        echo "\n";
                        
                        // Check if has Student role
                        $hasStudentRole = DB::connection('school')->table('model_has_roles')
                            ->where('role_id', $studentRole->id)
                            ->where('model_id', $user->id)
                            ->where('model_type', 'App\\Models\\User')
                            ->first();
                        
                        if (!$hasStudentRole) {
                            // Remove any existing roles and assign Student role
                            DB::connection('school')->table('model_has_roles')
                                ->where('model_id', $user->id)
                                ->where('model_type', 'App\\Models\\User')
                                ->delete();
                            
                            // Assign Student role
                            DB::connection('school')->table('model_has_roles')->insert([
                                'role_id' => $studentRole->id,
                                'model_id' => $user->id,
                                'model_type' => 'App\\Models\\User',
                            ]);
                            
                            $fixedCount++;
                            echo "    → Fixed: Assigned Student role to {$user->email}\n";
                        }
                    }
                }
                
                echo "  Fixed {$fixedCount} student role assignments\n";
                
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
