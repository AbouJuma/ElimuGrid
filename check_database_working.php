<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== WORKING DATABASE CHECK ===\n\n";

// Check database connection
echo "Database: " . DB::getDatabaseName() . "\n";

// List all tables
echo "\nTables containing 'virtual_classroom':\n";
$tables = DB::select("SHOW TABLES LIKE '%virtual_classroom%'");
foreach ($tables as $table) {
    echo "  - " . $table->Tables_in_eschool_saas_3_baobab . "\n";
}

// Check if virtual_classrooms table exists
$exists = DB::getSchemaBuilder()->hasTable('virtual_classrooms');
echo "\nvirtual_classrooms table exists: " . ($exists ? 'YES' : 'NO') . "\n";

if (!$exists) {
    echo "\n❌ Creating virtual_classrooms table...\n";
    
    // Create table directly
    DB::statement("
        CREATE TABLE virtual_classrooms (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(191) NOT NULL,
            description TEXT NULL,
            class_id BIGINT UNSIGNED NOT NULL,
            section_id BIGINT UNSIGNED NULL,
            subject_id BIGINT UNSIGNED NOT NULL,
            teacher_id BIGINT UNSIGNED NOT NULL,
            room_name VARCHAR(191) NOT NULL UNIQUE,
            meeting_url VARCHAR(191) NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            status ENUM('scheduled', 'live', 'completed', 'cancelled') DEFAULT 'scheduled',
            created_by BIGINT UNSIGNED NOT NULL,
            school_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            INDEX idx_school_id (school_id),
            INDEX idx_class_id (class_id),
            INDEX idx_section_id (section_id),
            INDEX idx_teacher_id (teacher_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✅ Table created successfully\n";
} else {
    echo "\n✅ Table already exists\n";
}

echo "\n=== CHECK COMPLETE ===\n";
