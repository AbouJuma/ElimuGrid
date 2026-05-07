<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DebugStudentDataSeeder extends Seeder
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
                
                // Check students
                $students = DB::connection('school')->table('students')->get();
                echo "Students table ({$students->count()} records):\n";
                foreach ($students as $student) {
                    echo "  Student ID: {$student->id}, User ID: {$student->user_id}, Class ID: {$student->class_id}, Class Section ID: {$student->class_section_id}\n";
                    
                    // Check if user exists
                    $user = DB::connection('school')->table('users')
                        ->where('id', $student->user_id)
                        ->first();
                    
                    if ($user) {
                        echo "    → User exists: {$user->first_name} {$user->last_name} ({$user->email})\n";
                        
                        // Check if has Student role
                        $hasRole = DB::connection('school')->table('model_has_roles')
                            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                            ->where('model_has_roles.model_id', $user->id)
                            ->where('model_has_roles.model_type', 'App\\Models\\User')
                            ->where('roles.name', 'Student')
                            ->first();
                        
                        if ($hasRole) {
                            echo "    → Has Student role: YES\n";
                        } else {
                            echo "    → Has Student role: NO\n";
                        }
                    } else {
                        echo "    → User NOT FOUND!\n";
                    }
                }
                
                // Check users with Student role
                $studentUsers = DB::connection('school')->table('users')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('roles.name', 'Student')
                    ->whereNull('users.deleted_at')
                    ->select('users.*')
                    ->get();
                
                echo "\nUsers with Student role ({$studentUsers->count()} records):\n";
                foreach ($studentUsers as $user) {
                    echo "  User ID: {$user->id}, Email: {$user->email}, Name: {$user->first_name} {$user->last_name}\n";
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
