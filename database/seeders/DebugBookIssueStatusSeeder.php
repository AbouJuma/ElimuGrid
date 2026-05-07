<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DebugBookIssueStatusSeeder extends Seeder
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
                
                // Get raw data
                $rawData = DB::connection('school')->table('book_issues')
                    ->selectRaw('id, status, CAST(status AS CHAR) as status_char, LENGTH(status) as status_length')
                    ->get();
                
                foreach ($rawData as $row) {
                    echo "  Issue ID: {$row->id}\n";
                    echo "    → status (raw): " . var_export($row->status, true) . "\n";
                    echo "    → status (char): '{$row->status_char}'\n";
                    echo "    → status length: {$row->status_length}\n";
                    echo "    → status type: " . gettype($row->status) . "\n";
                }
                
                // Try different update approaches
                echo "\n  Trying different update approaches:\n";
                
                // Approach 1: Update where status = 0 (integer)
                $affected1 = DB::connection('school')->table('book_issues')
                    ->where('status', 0)
                    ->update(['status' => 'borrowed']);
                echo "    Approach 1 (status = 0): {$affected1} records\n";
                
                // Approach 2: Update where status = '0' (string)
                $affected2 = DB::connection('school')->table('book_issues')
                    ->where('status', '0')
                    ->update(['status' => 'borrowed']);
                echo "    Approach 2 (status = '0'): {$affected2} records\n";
                
                // Approach 3: Update all records
                $affected3 = DB::connection('school')->table('book_issues')
                    ->update(['status' => 'borrowed']);
                echo "    Approach 3 (all): {$affected3} records\n";
                
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
