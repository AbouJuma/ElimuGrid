<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FixBookIssueStudentIdSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Fixing book_issue student_id references...\n";
        
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
                
                // Get all book issues
                $bookIssues = DB::connection('school')->table('book_issues')->get();
                $fixedCount = 0;
                
                foreach ($bookIssues as $issue) {
                    // Check if student_id refers to students.id or students.user_id
                    $studentById = DB::connection('school')->table('students')
                        ->where('id', $issue->student_id)
                        ->first();
                    
                    $studentByUserId = DB::connection('school')->table('students')
                        ->where('user_id', $issue->student_id)
                        ->first();
                    
                    if ($studentById && !$studentByUserId) {
                        // student_id refers to students.id, we need to change it to students.user_id
                        echo "  Issue {$issue->id}: Converting student_id from students.id ({$issue->student_id}) to students.user_id ({$studentById->user_id})\n";
                        
                        DB::connection('school')->table('book_issues')
                            ->where('id', $issue->id)
                            ->update(['student_id' => $studentById->user_id]);
                        
                        $fixedCount++;
                    } elseif ($studentByUserId) {
                        echo "  Issue {$issue->id}: student_id already correct (user_id: {$issue->student_id})\n";
                    } else {
                        echo "  Issue {$issue->id}: student_id {$issue->student_id} not found in students table!\n";
                    }
                }
                
                echo "  Fixed {$fixedCount} book issue student references\n";
                
                // Verify the fix
                echo "\n  Verification:\n";
                $verifiedIssues = DB::connection('school')->table('book_issues')->get();
                foreach ($verifiedIssues as $issue) {
                    $student = DB::connection('school')->table('students')
                        ->where('user_id', $issue->student_id)
                        ->first();
                    
                    if ($student) {
                        $user = DB::connection('school')->table('users')
                            ->where('id', $student->user_id)
                            ->first();
                        echo "    Issue {$issue->id}: Student = {$user->first_name} {$user->last_name} ✓\n";
                    } else {
                        echo "    Issue {$issue->id}: Student NOT FOUND ✗\n";
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
