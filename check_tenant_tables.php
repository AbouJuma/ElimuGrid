<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Checking Tenant Tables ===\n";

try {
    // Check main database tables
    echo "\nMain database tables:\n";
    $mainTables = DB::select('SHOW TABLES');
    foreach ($mainTables as $table) {
        $tableName = array_values((array)$table)[0];
        if (strpos($tableName, 's1_') === 0) {
            echo "✓ {$tableName}\n";
        }
    }
    
    // Switch to tenant and check
    echo "\nSwitching to tenant 1...\n";
    App\Services\SharedHostingTenantService::switchToTenant(1);
    
    $tenantTables = DB::select('SHOW TABLES');
    echo "Tenant tables found: " . count($tenantTables) . "\n";
    
    if (empty($tenantTables)) {
        echo "No tenant tables found. Creating basic tables...\n";
        
        // Create basic tables directly
        $basicTables = [
            'users' => 'CREATE TABLE s1_users (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                mobile VARCHAR(20),
                image VARCHAR(255),
                password VARCHAR(255),
                remember_token VARCHAR(100),
                email_verified_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )',
            
            'classes' => 'CREATE TABLE s1_classes (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                medium_id BIGINT,
                school_id BIGINT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            )'
        ];
        
        foreach ($basicTables as $name => $sql) {
            try {
                DB::statement($sql);
                echo "✓ Created table: {$name}\n";
            } catch (Exception $e) {
                echo "❌ Error creating {$name}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Test user creation
    echo "\nTesting user creation...\n";
    try {
        DB::table('users')->insert([
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✓ Test user created successfully\n";
        
        $user = DB::table('users')->where('email', 'test@example.com')->first();
        echo "✓ User found: {$user->first_name} {$user->last_name}\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    App\Services\SharedHostingTenantService::switchToMain();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
