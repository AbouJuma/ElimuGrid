<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== CREATE VIRTUAL CLASSROOM TABLE DIRECTLY ===\n\n";

// Check if table exists
$tableExists = Schema::hasTable('virtual_classrooms');
echo "Table exists: " . ($tableExists ? 'YES' : 'NO') . "\n";

if (!$tableExists) {
    echo "Creating virtual_classrooms table...\n";
    
    // Create table using raw SQL
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
    echo "ℹ️  Table already exists\n";
}

// Check if attendance table exists
$attendanceExists = Schema::hasTable('virtual_classroom_attendance');
echo "Attendance table exists: " . ($attendanceExists ? 'YES' : 'NO') . "\n";

if (!$attendanceExists) {
    echo "Creating virtual_classroom_attendance table...\n";
    
    DB::statement("
        CREATE TABLE virtual_classroom_attendance (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            virtual_classroom_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            joined_at TIMESTAMP NOT NULL,
            left_at TIMESTAMP NULL,
            duration_seconds INT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            FOREIGN KEY (virtual_classroom_id) REFERENCES virtual_classrooms(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            INDEX idx_virtual_classroom_id (virtual_classroom_id),
            INDEX idx_student_id (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✅ Attendance table created successfully\n";
} else {
    echo "ℹ️  Attendance table already exists\n";
}

echo "\n=== CREATION COMPLETE ===\n";
