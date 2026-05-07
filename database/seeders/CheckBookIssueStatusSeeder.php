<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CheckBookIssueStatusSeeder extends Seeder
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
                
                // Check book_issues table
                $bookIssues = DB::connection('school')->table('book_issues')->get();
                echo "Book issues table ({$bookIssues->count()} records):\n";
                
                foreach ($bookIssues as $issue) {
                    echo "  Issue ID: {$issue->id}, Book ID: {$issue->book_id}, Student ID: {$issue->student_id}\n";
                    echo "    → status: '{$issue->status}'\n";
                    echo "    → issue_date: {$issue->issue_date}\n";
                    echo "    → return_date: {$issue->return_date}\n";
                    echo "    → actual_return_date: " . ($issue->actual_return_date ?? 'NULL') . "\n";
                    echo "    → late_days: {$issue->late_days}\n";
                    echo "    → fine_amount: {$issue->fine_amount}\n";
                }
                
                // Check if there are any book issues
                if ($bookIssues->count() == 0) {
                    echo "  No book issues found. Creating a test record...\n";
                    
                    // Get a book and student
                    $book = DB::connection('school')->table('books')->first();
                    $student = DB::connection('school')->table('students')->first();
                    
                    if ($book && $student) {
                        DB::connection('school')->table('book_issues')->insert([
                            'book_id' => $book->id,
                            'student_id' => $student->id,
                            'class_id' => $student->class_id,
                            'issue_date' => now()->subDays(5),
                            'return_date' => now()->addDays(5),
                            'status' => 'borrowed',
                            'school_id' => 3,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        echo "  Created test book issue record\n";
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
    }
}
