<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PopulateClassDataSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Populating class data in school databases...\n";
        
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
                
                // Get existing class sections and create corresponding class_schools entries
                $classSections = DB::connection('school')->table('class_sections')->get();
                $createdClasses = [];
                
                foreach ($classSections as $section) {
                    // Check if class_schools entry exists for this class_id
                    if (!isset($createdClasses[$section->class_id]) && $section->class_id) {
                        $existingClass = DB::connection('school')->table('class_schools')
                            ->where('id', $section->class_id)
                            ->first();
                        
                        if (!$existingClass) {
                            // Create a basic class entry
                            $classId = DB::connection('school')->table('class_schools')->insertGetId([
                                'id' => $section->class_id,
                                'name' => 'Class ' . $section->class_id,
                                'school_id' => $school->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $createdClasses[$section->class_id] = true;
                            echo "  Created class entry for class_id {$section->class_id}\n";
                        } else {
                            $createdClasses[$section->class_id] = true;
                        }
                    }
                }
                
                // Update students to ensure they have class_id set
                $students = DB::connection('school')->table('students')->get();
                foreach ($students as $student) {
                    if ($student->class_section_id && !$student->class_id) {
                        $classSection = DB::connection('school')->table('class_sections')
                            ->where('id', $student->class_section_id)
                            ->first();
                        
                        if ($classSection) {
                            DB::connection('school')->table('students')
                                ->where('id', $student->id)
                                ->update(['class_id' => $classSection->class_id]);
                            echo "  Updated student {$student->id} with class_id {$classSection->class_id}\n";
                        }
                    }
                }
                
                // Count final results
                $studentCount = DB::connection('school')->table('students')->count();
                $classCount = DB::connection('school')->table('class_schools')->count();
                $studentUsersCount = DB::connection('school')->table('users')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('roles.name', 'Student')
                    ->whereNull('users.deleted_at')
                    ->count();
                
                echo "  Final counts: {$studentCount} students, {$classCount} classes, {$studentUsersCount} student users\n";
                
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
