<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DATABASE CONFIGURATION CHECK ===\n\n";

// Check database connection details
echo "Database Configuration:\n";
echo "  - Default Connection: " . config('database.default') . "\n";
echo "  - Database Name: " . config('database.connections.mysql.database') . "\n";
echo "  - Prefix: " . config('database.connections.mysql.prefix') . "\n";

// Check current database
echo "\nCurrent Database Connection:\n";
echo "  - Driver: " . DB::getDriverName() . "\n";
echo "  - Database: " . DB::getDatabaseName() . "\n";

// Check if tables exist with correct names
echo "\nTable Check:\n";
$virtualClassroomExists = DB::getSchemaBuilder()->hasTable('virtual_classrooms');
$attendanceExists = DB::getSchemaBuilder()->hasTable('virtual_classroom_attendance');

echo "  - virtual_classrooms: " . ($virtualClassroomExists ? 'EXISTS' : 'MISSING') . "\n";
echo "  - virtual_classroom_attendance: " . ($attendanceExists ? 'EXISTS' : 'MISSING') . "\n";

// Check what Laravel is actually looking for
echo "\nDebug Query:\n";
try {
    $result = DB::table('virtual_classrooms')->count();
    echo "  - Direct query result: " . $result . " records found\n";
    
    // Show the actual SQL that Laravel generates
    $query = DB::table('virtual_classrooms')->toSql();
    echo "  - Generated SQL: " . $query . "\n";
    
} catch (Exception $e) {
    echo "  - Query error: " . $e->getMessage() . "\n";
}

echo "\n=== CONFIGURATION CHECK COMPLETE ===\n";
