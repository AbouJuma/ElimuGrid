<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CheckModelHasRolesSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        foreach ($schools as $school) {
            if (!$school->database_name || $school->id != 3) {
                continue; // Only check Baobab school (ID 3)
            }
            
            echo "\n=== School: {$school->name} (DB: {$school->database_name}) ===\n";
            
            try {
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');
                
                // Check model_has_roles table
                $modelRoles = DB::connection('school')->table('model_has_roles')->get();
                echo "model_has_roles table ({$modelRoles->count()} records):\n";
                foreach ($modelRoles as $mr) {
                    $user = DB::connection('school')->table('users')
                        ->where('id', $mr->model_id)
                        ->first();
                    $role = DB::connection('school')->table('roles')
                        ->where('id', $mr->role_id)
                        ->first();
                    
                    echo "  User ID: {$mr->model_id}, Role ID: {$mr->role_id}, Model Type: '{$mr->model_type}'\n";
                    if ($user) {
                        echo "    → User: {$user->first_name} {$user->last_name} ({$user->email})\n";
                    }
                    if ($role) {
                        echo "    → Role: {$role->name}\n";
                    }
                }
                
                // Test the exact query from BookIssueController
                echo "\nTesting student query:\n";
                $studentUsers = DB::connection('school')->table('users')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->join('students', 'users.id', '=', 'students.user_id')
                    ->join('class_sections', 'students.class_section_id', '=', 'class_sections.id')
                    ->where('roles.name', 'Student')
                    ->where('class_sections.class_id', 1) // Test with class_id 1
                    ->where('users.school_id', 3) // Baobab school
                    ->whereNull('users.deleted_at')
                    ->whereNull('students.deleted_at')
                    ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                    ->get();
                
                echo "Query result: {$studentUsers->count()} students found\n";
                foreach ($studentUsers as $student) {
                    echo "  - {$student->first_name} {$student->last_name} ({$student->email})\n";
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
    }
}
