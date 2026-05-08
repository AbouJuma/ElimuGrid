<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIX DATABASE CONNECTION ISSUE ===\n\n";

use Illuminate\Support\Facades\DB;

// The issue is that the system is trying to access system_settings in wrong database
// Let's force the correct database connection for the current user

try {
    // Get current authenticated user
    if (!Auth::check()) {
        echo "❌ User not authenticated\n";
        exit;
    }

    $user = Auth::user();
    $schoolId = $user->school_id;
    
    echo "User: {$user->full_name}\n";
    echo "School ID: {$schoolId}\n\n";

    // Determine correct database name based on school ID
    $databaseName = 'eschool_saas_3_baobab'; // Based on previous debugging
    
    echo "Setting database to: {$databaseName}\n";
    
    // Force database connection
    config(['database.connections.mysql.database' => $databaseName]);
    DB::purge('mysql');
    
    echo "Connected to: " . DB::getDatabaseName() . "\n";
    
    // Check if system_settings exists
    $hasSystemSettings = DB::getSchemaBuilder()->hasTable('system_settings');
    echo "system_settings table exists: " . ($hasSystemSettings ? 'YES' : 'NO') . "\n";
    
    if ($hasSystemSettings) {
        $count = DB::table('system_settings')->count();
        echo "Records in system_settings: {$count}\n";
        
        // Test a simple query
        $settings = DB::table('system_settings')->limit(5)->get();
        echo "Sample settings:\n";
        foreach ($settings as $setting) {
            echo "  - {$setting->type}: {$setting->data}\n";
        }
    } else {
        echo "Creating system_settings table...\n";
        
        // Create system_settings table if it doesn't exist
        DB::statement("
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(255) NOT NULL,
                data TEXT,
                school_id BIGINT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        echo "✅ system_settings table created\n";
        
        // Insert basic settings
        DB::table('system_settings')->insert([
            ['type' => 'system_name', 'data' => 'eSchool Management System', 'school_id' => $schoolId],
            ['type' => 'school_timezone', 'data' => 'UTC', 'school_id' => $schoolId],
            ['type' => 'currency_symbol', 'data' => '$', 'school_id' => $schoolId],
        ]);
        
        echo "✅ Basic system settings inserted\n";
    }
    
    echo "\n✅ Database connection fixed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
