<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class RecreateBookIssueSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        foreach ($schools as $school) {
            if (!$school->database_name || $school->id != 3) {
                continue; // Only fix Baobab school (ID 3)
            }
            
            echo "\n=== Processing school: {$school->name} (DB: {$school->database_name}) ===\n";
            
            try {
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');
                
                // Get existing book issue data
                $existingIssue = DB::connection('school')->table('book_issues')->first();
                
                if ($existingIssue) {
                    echo "  Deleting existing issue ID {$existingIssue->id}\n";
                    DB::connection('school')->table('book_issues')->where('id', $existingIssue->id)->delete();
                    
                    // Recreate with proper status
                    $newId = DB::connection('school')->table('book_issues')->insertGetId([
                        'book_id' => $existingIssue->book_id,
                        'student_id' => $existingIssue->student_id,
                        'class_id' => $existingIssue->class_id,
                        'issue_date' => $existingIssue->issue_date,
                        'return_date' => $existingIssue->return_date,
                        'status' => 'borrowed', // Proper text status
                        'school_id' => $existingIssue->school_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    echo "  Created new issue ID {$newId} with status 'borrowed'\n";
                } else {
                    // Create a new test issue
                    $book = DB::connection('school')->table('books')->first();
                    $student = DB::connection('school')->table('students')->first();
                    
                    if ($book && $student) {
                        $newId = DB::connection('school')->table('book_issues')->insertGetId([
                            'book_id' => $book->id,
                            'student_id' => $student->user_id,
                            'class_id' => $student->class_id,
                            'issue_date' => now()->subDays(5)->format('Y-m-d'),
                            'return_date' => now()->addDays(5)->format('Y-m-d'),
                            'status' => 'borrowed',
                            'school_id' => 3,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        echo "  Created test issue ID {$newId} with status 'borrowed'\n";
                    }
                }
                
                // Verify final result
                $finalData = DB::connection('school')->table('book_issues')->get();
                foreach ($finalData as $row) {
                    echo "  Final: Issue ID {$row->id}, status = '{$row->status}'\n";
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
