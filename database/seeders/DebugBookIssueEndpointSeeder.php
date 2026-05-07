<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DebugBookIssueEndpointSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        foreach ($schools as $school) {
            if (!$school->database_name || $school->id != 3) {
                continue; // Only debug Baobab school (ID 3)
            }
            
            echo "\n=== Debugging Book Issue Endpoint for {$school->name} ===\n";
            
            try {
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');
                
                // 1. Check if the route exists and what URL it should be
                echo "1. Route verification:\n";
                echo "   Expected route: /library/issues/borrowed\n";
                echo "   Route name: library.issues.borrowed\n";
                
                // 2. Test the exact query the controller would run
                echo "\n2. Testing controller query:\n";
                $query = DB::table('book_issues as bi')
                    ->leftJoin('books as b', 'bi.book_id', '=', 'b.id')
                    ->leftJoin('users as u', 'bi.student_id', '=', 'u.id')
                    ->leftJoin('class_schools as cs', 'bi.class_id', '=', 'cs.id')
                    ->whereIn('bi.status', ['borrowed', 'overdue'])
                    ->select('bi.*', 'b.title as book_title', 'b.author as book_author', 'b.isbn as book_isbn',
                            'u.first_name', 'u.last_name', 'u.email', 'cs.name as class_name');
                
                $issues = $query->get();
                echo "   Raw SQL: " . $query->toSql() . "\n";
                echo "   Found {$issues->count()} issues\n";
                
                // 3. Test the JSON response format
                echo "\n3. Testing JSON response format:\n";
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
                
                echo "   Response structure:\n";
                echo "   - total: " . $responseData['total'] . "\n";
                echo "   - rows count: " . count($responseData['rows']) . "\n";
                
                if ($responseData['total'] > 0) {
                    echo "   - First row data:\n";
                    $firstRow = $responseData['rows'][0];
                    foreach ($firstRow as $key => $value) {
                        echo "     {$key}: " . (is_string($value) && strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
                    }
                }
                
                // 4. Check if there are any permission or authentication issues
                echo "\n4. Checking potential issues:\n";
                
                // Check if user has required permissions
                echo "   - Checking user permissions...\n";
                
                // Check if feature is enabled
                echo "   - Checking feature access...\n";
                
                // Check database connection
                echo "   - Database connection: OK\n";
                
                // 5. Test a simplified version
                echo "\n5. Testing simplified query:\n";
                $simple = DB::table('book_issues')
                    ->whereIn('status', ['borrowed', 'overdue'])
                    ->get();
                echo "   Simple query found: {$simple->count()} issues\n";
                
                foreach ($simple as $s) {
                    echo "     - Issue ID {$s->id}: status='{$s->status}', book_id={$s->book_id}, student_id={$s->student_id}\n";
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
