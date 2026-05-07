<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckSchoolTablesSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Checking tables in school databases...\n";
        
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
                
                $schema = DB::connection('school')->getSchemaBuilder();
                
                // Check key tables
                $tables = [
                    'users' => 'Users table',
                    'students' => 'Students table',
                    'class_schools' => 'Class Schools table',
                    'class_sections' => 'Class Sections table',
                    'model_has_roles' => 'Model Has Roles table',
                    'roles' => 'Roles table',
                    'books' => 'Books table',
                    'book_issues' => 'Book Issues table',
                ];
                
                foreach ($tables as $table => $desc) {
                    $exists = $schema->hasTable($table);
                    echo "  " . ($exists ? "✓" : "✗") . " {$desc} ({$table})\n";
                    
                    if ($table === 'students' && $exists) {
                        $count = DB::connection('school')->table('students')->count();
                        echo "    → {$count} student records\n";
                    }
                    
                    if ($table === 'class_sections' && $exists) {
                        $count = DB::connection('school')->table('class_sections')->count();
                        echo "    → {$count} class section records\n";
                    }
                    
                    if ($table === 'users' && $exists) {
                        $studentCount = DB::connection('school')->table('users')
                            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                            ->where('roles.name', 'Student')
                            ->whereNull('users.deleted_at')
                            ->count();
                        echo "    → {$studentCount} student users\n";
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
    }
}
