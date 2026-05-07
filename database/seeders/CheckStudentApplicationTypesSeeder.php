<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CheckStudentApplicationTypesSeeder extends Seeder
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
                
                // Check students table
                $students = DB::connection('school')->table('students')->get();
                echo "Students table ({$students->count()} records):\n";
                
                foreach ($students as $student) {
                    echo "  Student ID: {$student->id}, User ID: {$student->user_id}\n";
                    echo "    → application_type: '{$student->application_type}'\n";
                    echo "    → application_status: {$student->application_status}\n";
                    echo "    → class_section_id: {$student->class_section_id}\n";
                    echo "    → session_year_id: {$student->session_year_id}\n";
                    echo "    → school_id: {$student->school_id}\n";
                    
                    // Check if user exists and is active
                    $user = DB::connection('school')->table('users')
                        ->where('id', $student->user_id)
                        ->first();
                    
                    if ($user) {
                        echo "    → User status: {$user->status}\n";
                        echo "    → User deleted_at: " . ($user->deleted_at ?? 'NULL') . "\n";
                    } else {
                        echo "    → User NOT FOUND!\n";
                    }
                }
                
                // Test the problematic query
                echo "\nTesting the original query:\n";
                $originalQuery = DB::connection('school')->table('students')
                    ->where('application_type', 'offline')
                    ->where('application_type', 'online')
                    ->count();
                echo "Original query result: {$originalQuery} students\n";
                
                echo "\nTesting corrected query (OR instead of AND):\n";
                $correctedQuery = DB::connection('school')->table('students')
                    ->where(function ($query) {
                        $query->where('application_type', 'offline')
                            ->orWhere('application_type', 'online');
                    })
                    ->count();
                echo "Corrected query result: {$correctedQuery} students\n";
                
                echo "\nTesting query with application_status filter:\n";
                $statusQuery = DB::connection('school')->table('students')
                    ->where(function ($query) {
                        $query->where('application_type', 'offline')
                            ->orWhere('application_type', 'online')
                            ->orWhere(function ($q) {
                                $q->where('application_status', 1);
                            });
                    })
                    ->count();
                echo "Status query result: {$statusQuery} students\n";
                
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
