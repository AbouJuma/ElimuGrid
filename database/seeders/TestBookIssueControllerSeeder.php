<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TestBookIssueControllerSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        foreach ($schools as $school) {
            if (!$school->database_name || $school->id != 3) {
                continue; // Only test Baobab school (ID 3)
            }
            
            echo "\n=== Testing BookIssue Controller Query for {$school->name} ===\n";
            
            try {
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');
                
                // Simulate the exact query from getBorrowedBooks
                echo "1. Testing base query:\n";
                $baseQuery = DB::connection('school')->table('book_issues')
                    ->whereIn('status', ['borrowed', 'overdue'])
                    ->get();
                echo "   Found {$baseQuery->count()} issues\n";
                
                // Test with joins like the Eloquent relationships would do
                echo "\n2. Testing with manual joins (simulating Eloquent):\n";
                $issuesWithJoins = DB::connection('school')->table('book_issues as bi')
                    ->leftJoin('books as b', 'bi.book_id', '=', 'b.id')
                    ->leftJoin('users as u', 'bi.student_id', '=', 'u.id')  // Direct join to users
                    ->leftJoin('class_schools as cs', 'bi.class_id', '=', 'cs.id')
                    ->whereIn('bi.status', ['borrowed', 'overdue'])
                    ->select('bi.*', 'b.title as book_title', 'b.author as book_author', 
                            'u.first_name', 'u.last_name', 'u.email', 'cs.name as class_name')
                    ->get();
                
                echo "   Found {$issuesWithJoins->count()} issues with joins\n";
                foreach ($issuesWithJoins as $issue) {
                    if ($issue->book_title) {
                        echo "   - Book: {$issue->book_title}\n";
                    }
                    if ($issue->first_name) {
                        echo "     Student: {$issue->first_name} {$issue->last_name} ({$issue->email})\n";
                    }
                    if ($issue->class_name) {
                        echo "     Class: {$issue->class_name}\n";
                    }
                    echo "     Status: {$issue->status}\n";
                    echo "\n";
                }
                
                // Test the specific format expected by the controller
                echo "\n3. Testing exact controller format:\n";
                $formattedData = $issuesWithJoins->map(function ($issue) {
                    return [
                        'id' => $issue->id,
                        'book_title' => $issue->book_title ?? 'Unknown Book',
                        'book_author' => $issue->book_author ?? 'Unknown Author',
                        'book_isbn' => '',  // Would need another join
                        'student_name' => ($issue->first_name ?? '') . ' ' . ($issue->last_name ?? ''),
                        'class_name' => $issue->class_name ?? 'Unknown Class',
                        'issue_date' => $issue->issue_date,
                        'return_date' => $issue->return_date,
                        'late_days' => 0,
                        'fine_amount' => 0.00,
                        'status' => $issue->status,
                        'status_badge' => $this->getStatusBadge($issue->status),
                        'operate' => '<button class="btn btn-sm btn-success return-book" data-id="' . $issue->id . '">Return</button>',
                    ];
                });
                
                echo "   Formatted data:\n";
                foreach ($formattedData as $row) {
                    echo "   - {$row['book_title']} borrowed by {$row['student_name']}\n";
                    echo "     Status: {$row['status_badge']}\n";
                    echo "     Action: {$row['operate']}\n";
                    echo "\n";
                }
                
            } catch (\Exception $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
                echo "  Trace: " . $e->getTraceAsString() . "\n";
            }
        }
        
        // Switch back to main database
        DB::setDefaultConnection('mysql');
        Config::set('database.connections.school.database', '');
        DB::purge('school');
        DB::connection('mysql')->reconnect();
        DB::setDefaultConnection('mysql');
    }
    
    private function getStatusBadge($status): string
    {
        switch ($status) {
            case 'borrowed':
                return '<span class="badge badge-info">Borrowed</span>';
            case 'returned':
                return '<span class="badge badge-success">Returned</span>';
            case 'overdue':
                return '<span class="badge badge-danger">Overdue</span>';
            default:
                return '<span class="badge badge-secondary">Unknown</span>';
        }
    }
}
