<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckBookIssueTableSeeder extends Seeder
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
                
                // Check table structure
                $columns = DB::connection('school')->select("SHOW COLUMNS FROM book_issues");
                echo "  Table structure:\n";
                foreach ($columns as $column) {
                    echo "    - {$column->Field}: {$column->Type} (Null: {$column->Null}, Default: " . ($column->Default ?? 'NULL') . ")\n";
                }
                
                // Fix the status column if it's not VARCHAR
                $statusColumn = collect($columns)->firstWhere('Field', 'status');
                if ($statusColumn && strpos(strtoupper($statusColumn->Type), 'INT') !== false) {
                    echo "  Status column is numeric type, converting to VARCHAR...\n";
                    
                    // Alter the table to change status to VARCHAR
                    DB::connection('school')->statement("ALTER TABLE book_issues MODIFY COLUMN status VARCHAR(20) DEFAULT 'borrowed'");
                    echo "  Changed status column to VARCHAR(20)\n";
                    
                    // Update existing records
                    DB::connection('school')->table('book_issues')
                        ->where('status', 0)
                        ->update(['status' => 'borrowed']);
                    
                    echo "  Updated existing records to 'borrowed'\n";
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
