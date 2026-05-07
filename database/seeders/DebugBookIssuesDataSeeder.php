<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DebugBookIssuesDataSeeder extends Seeder
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
                    echo "  Issue ID: {$issue->id}\n";
                    echo "    → book_id: {$issue->book_id}\n";
                    echo "    → student_id: {$issue->student_id}\n";
                    echo "    → class_id: {$issue->class_id}\n";
                    echo "    → status: '{$issue->status}'\n";
                    
                    // Check if related book exists
                    $book = DB::connection('school')->table('books')
                        ->where('id', $issue->book_id)
                        ->first();
                    if ($book) {
                        echo "    → Book: {$book->title} by {$book->author}\n";
                    } else {
                        echo "    → Book: NOT FOUND!\n";
                    }
                    
                    // Check if related student exists
                    $student = DB::connection('school')->table('students')
                        ->where('id', $issue->student_id)
                        ->first();
                    if ($student) {
                        $user = DB::connection('school')->table('users')
                            ->where('id', $student->user_id)
                            ->first();
                        if ($user) {
                            echo "    → Student: {$user->first_name} {$user->last_name}\n";
                        } else {
                            echo "    → Student user: NOT FOUND!\n";
                        }
                    } else {
                        echo "    → Student: NOT FOUND!\n";
                    }
                    
                    // Check if related class exists
                    $class = DB::connection('school')->table('class_schools')
                        ->where('id', $issue->class_id)
                        ->first();
                    if ($class) {
                        echo "    → Class: {$class->name}\n";
                    } else {
                        echo "    → Class: NOT FOUND!\n";
                    }
                }
                
                // Test the exact query from getBorrowedBooks
                echo "\nTesting the query from getBorrowedBooks:\n";
                $query = DB::connection('school')->table('book_issues')
                    ->whereIn('status', ['borrowed', 'overdue'])
                    ->get();
                
                echo "  Query result: {$query->count()} records\n";
                
                // Test with relationships
                echo "\nTesting with relationships (manual join):\n";
                $issuesWithRelations = DB::connection('school')->table('book_issues as bi')
                    ->leftJoin('books as b', 'bi.book_id', '=', 'b.id')
                    ->leftJoin('students as s', 'bi.student_id', '=', 's.id')
                    ->leftJoin('users as u', 's.user_id', '=', 'u.id')
                    ->leftJoin('class_schools as cs', 'bi.class_id', '=', 'cs.id')
                    ->whereIn('bi.status', ['borrowed', 'overdue'])
                    ->select('bi.*', 'b.title as book_title', 'b.author as book_author', 
                            'u.first_name', 'u.last_name', 'cs.name as class_name')
                    ->get();
                
                echo "  With relations: {$issuesWithRelations->count()} records\n";
                foreach ($issuesWithRelations as $issue) {
                    echo "    - Issue {$issue->id}: {$issue->book_title} borrowed by {$issue->first_name} {$issue->last_name}\n";
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
