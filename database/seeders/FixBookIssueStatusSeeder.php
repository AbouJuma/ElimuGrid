<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FixBookIssueStatusSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Fixing book issue status values in school databases...\n";
        
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
                
                // Fix status values
                $bookIssues = DB::connection('school')->table('book_issues')->get();
                $fixedCount = 0;
                
                foreach ($bookIssues as $issue) {
                    $newStatus = null;
                    
                    // Convert numeric status to text
                    if ($issue->status === '0' || $issue->status === 0) {
                        $newStatus = 'borrowed';
                    } elseif ($issue->status === '1' || $issue->status === 1) {
                        $newStatus = 'returned';
                    } elseif ($issue->status === '2' || $issue->status === 2) {
                        $newStatus = 'overdue';
                    }
                    
                    if ($newStatus && $newStatus !== $issue->status) {
                        DB::connection('school')->table('book_issues')
                            ->where('id', $issue->id)
                            ->update(['status' => $newStatus]);
                        
                        $fixedCount++;
                        echo "  Fixed issue ID {$issue->id}: '{$issue->status}' → '{$newStatus}'\n";
                    }
                }
                
                echo "  Fixed {$fixedCount} book issue status values\n";
                
                // Show final status
                $finalIssues = DB::connection('school')->table('book_issues')->get();
                echo "  Final status values:\n";
                foreach ($finalIssues as $issue) {
                    echo "    Issue ID {$issue->id}: status = '{$issue->status}'\n";
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
