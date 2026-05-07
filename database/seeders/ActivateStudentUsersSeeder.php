<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ActivateStudentUsersSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Activating student users in school databases...\n";
        
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
                
                // Get all students and activate their user accounts
                $students = DB::connection('school')->table('students')->get();
                $activatedCount = 0;
                
                foreach ($students as $student) {
                    // Check if user exists
                    $user = DB::connection('school')->table('users')
                        ->where('id', $student->user_id)
                        ->first();
                    
                    if ($user && $user->status == 0) {
                        // Activate the user
                        DB::connection('school')->table('users')
                            ->where('id', $user->id)
                            ->update(['status' => 1]);
                        
                        // Also clear deleted_at if it has a value
                        DB::connection('school')->table('users')
                            ->where('id', $user->id)
                            ->whereNotNull('deleted_at')
                            ->update(['deleted_at' => null]);
                        
                        $activatedCount++;
                        echo "  Activated user: {$user->first_name} {$user->last_name} ({$user->email})\n";
                    }
                }
                
                echo "  Activated {$activatedCount} student users\n";
                
                // Count final results
                $activeStudentUsers = DB::connection('school')->table('users')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('roles.name', 'Student')
                    ->where('users.status', 1)
                    ->whereNull('users.deleted_at')
                    ->count();
                
                echo "  Total active student users: {$activeStudentUsers}\n";
                
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
