<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECK DATABASE SETTINGS ISSUE ===\n\n";

use Illuminate\Support\Facades\DB;

// Check current database connection
echo "Current database connection:\n";
echo "Database: " . DB::getDatabaseName() . "\n";
echo "Connection name: " . DB::connection()->getName() . "\n\n";

// Check if system_settings table exists in current database
echo "Checking system_settings table in current database:\n";
$hasSystemSettings = DB::getSchemaBuilder()->hasTable('system_settings');
echo "system_settings table exists: " . ($hasSystemSettings ? 'YES' : 'NO') . "\n";

if ($hasSystemSettings) {
    $count = DB::table('system_settings')->count();
    echo "Records in system_settings: {$count}\n";
}

echo "\nChecking other potential databases:\n";

// Check common database names
$potentialDbs = ['eschool_saas', 'eschool_saas_3_baobab', 'ecofield_elimu'];

foreach ($potentialDbs as $dbName) {
    echo "\nChecking database: {$dbName}\n";
    
    try {
        // Switch to database
        config(['database.connections.mysql.database' => $dbName]);
        DB::purge('mysql');
        
        echo "  Connected to: " . DB::getDatabaseName() . "\n";
        
        // Check if system_settings exists
        $hasSystemSettings = DB::getSchemaBuilder()->hasTable('system_settings');
        echo "  - system_settings table: " . ($hasSystemSettings ? 'EXISTS' : 'MISSING') . "\n";
        
        if ($hasSystemSettings) {
            $count = DB::table('system_settings')->count();
            echo "  - Records: {$count}\n";
        }
        
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
    }
}

// Restore to default database
config(['database.connections.mysql.database' => 'eschool_saas']);
DB::purge('mysql');

echo "\n=== CHECK COMPLETE ===\n";
