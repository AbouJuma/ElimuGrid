<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ForceFixBookIssueStatusSeeder extends Seeder
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
                
                // Direct SQL update to fix the status
                $affected = DB::connection('school')->table('book_issues')
                    ->where('status', '0')
                    ->update(['status' => 'borrowed']);
                
                echo "  Updated {$affected} records from '0' to 'borrowed'\n";
                
                // Verify the fix
                $issues = DB::connection('school')->table('book_issues')->get();
                foreach ($issues as $issue) {
                    echo "  Issue ID {$issue->id}: status = '{$issue->status}'\n";
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
