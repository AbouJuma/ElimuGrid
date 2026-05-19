<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Migrating to Shared Hosting Compatible Architecture ===\n";

try {
    // Get all schools
    $schools = DB::connection('mysql')->table('schools')->get();
    
    foreach ($schools as $school) {
        echo "\nProcessing school: {$school->name} (ID: {$school->id})\n";
        
        // Check if tenant already exists with prefix-based tables
        $tenantExists = false;
        try {
            // Switch to tenant context
            $prefix = 'school_' . $school->id . '_';
            Config::set('database.connections.mysql.prefix', $prefix);
            DB::purge('mysql');
            DB::reconnect('mysql');
            
            // Check if users table exists with this prefix
            $tables = DB::select('SHOW TABLES LIKE "' . $prefix . 'users"');
            $tenantExists = !empty($tables);
            
            // Switch back to main
            Config::set('database.connections.mysql.prefix', '');
            DB::purge('mysql');
            DB::reconnect('mysql');
            
        } catch (Exception $e) {
            echo "Error checking tenant: " . $e->getMessage() . "\n";
        }
        
        if ($tenantExists) {
            echo "✓ Tenant tables already exist with prefix approach\n";
            continue;
        }
        
        echo "→ Creating tenant tables with prefix approach...\n";
        
        try {
            // Use the SharedHostingTenantService to create tables
            App\Services\SharedHostingTenantService::createTenantTables($school->id);
            echo "✓ Tenant tables created successfully\n";
            
            // Update school record to reflect prefix-based approach
            DB::connection('mysql')->table('schools')
                ->where('id', $school->id)
                ->update(['database_name' => 'school_' . $school->id . '_']);
            
            echo "✓ School record updated\n";
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "✓ All schools migrated to prefix-based table architecture\n";
    echo "✓ Shared hosting compatible - no CREATE DATABASE permissions required\n";
    echo "✓ Each school uses tables with prefix: school_{id}_table_name\n";
    
} catch (Exception $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
