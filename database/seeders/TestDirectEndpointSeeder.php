<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TestDirectEndpointSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        foreach ($schools as $school) {
            if (!$school->database_name || $school->id != 3) {
                continue; // Only test Baobab school (ID 3)
            }
            
            echo "\n=== Testing Direct Endpoint Access for {$school->name} ===\n";
            
            try {
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');
                
                // Simulate the controller method directly
                echo "1. Simulating getBorrowedBooks method:\n";
                
                // Simulate authentication
                $user = DB::connection('school')->table('users')
                    ->where('email', 'aboukisiaki@gmail.com')
                    ->first();
                
                if ($user) {
                    echo "   Found user: {$user->first_name} {$user->last_name} (ID: {$user->id})\n";
                    
                    // Check permissions
                    echo "   Checking permissions...\n";
                    
                    // Run the query
                    $query = DB::connection('school')->table('book_issues as bi')
                        ->leftJoin('books as b', 'bi.book_id', '=', 'b.id')
                        ->leftJoin('users as u', 'bi.student_id', '=', 'u.id')
                        ->leftJoin('class_schools as cs', 'bi.class_id', '=', 'cs.id')
                        ->whereIn('bi.status', ['borrowed', 'overdue'])
                        ->select('bi.*', 'b.title as book_title', 'b.author as book_author', 'b.isbn as book_isbn',
                                'u.first_name', 'u.last_name', 'u.email', 'cs.name as class_name');
                    
                    $issues = $query->get();
                    echo "   Query executed successfully\n";
                    echo "   Found {$issues->count()} issues\n";
                    
                    // Format response
                    $responseData = [
                        'total' => $issues->count(),
                        'rows' => $issues->map(function ($issue) {
                            return [
                                'id' => $issue->id,
                                'book_title' => $issue->book_title ?? 'Unknown Book',
                                'book_author' => $issue->book_author ?? 'Unknown Author',
                                'book_isbn' => $issue->book_isbn ?? '',
                                'student_name' => trim(($issue->first_name ?? '') . ' ' . ($issue->last_name ?? '')),
                                'class_name' => $issue->class_name ?? 'Unknown Class',
                                'issue_date' => $issue->issue_date,
                                'return_date' => $issue->return_date,
                                'late_days' => 0,
                                'fine_amount' => 0.00,
                                'status' => $issue->status,
                                'status_badge' => $this->getStatusBadge($issue->status),
                                'operate' => '<button class="btn btn-sm btn-success return-book" data-id="' . $issue->id . '">Return</button>',
                            ];
                        })
                    ];
                    
                    echo "   Response formatted successfully\n";
                    echo "   Total: {$responseData['total']}\n";
                    echo "   Rows: " . count($responseData['rows']) . "\n";
                    
                    if ($responseData['total'] > 0) {
                        echo "   Sample data:\n";
                        $sample = $responseData['rows'][0];
                        echo "     - Book: {$sample['book_title']}\n";
                        echo "     - Student: {$sample['student_name']}\n";
                        echo "     - Class: {$sample['class_name']}\n";
                        echo "     - Status: {$sample['status']}\n";
                    }
                    
                    // Test JSON serialization
                    $json = json_encode($responseData);
                    echo "   JSON serialization: " . ($json !== false ? "SUCCESS" : "FAILED") . "\n";
                    
                } else {
                    echo "   User not found!\n";
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
