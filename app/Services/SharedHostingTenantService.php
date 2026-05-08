<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\School;

class SharedHostingTenantService
{
    /**
     * Switch to tenant database using table prefixes
     * 
     * @param int $schoolId
     * @return void
     */
    public static function switchToTenant($schoolId)
    {
        $school = School::find($schoolId);
        
        if (!$school) {
            throw new \Exception("School not found: {$schoolId}");
        }
        
        // Set table prefix for this tenant
        $prefix = 'school_' . $schoolId . '_';
        
        // Update database configuration to use prefix
        Config::set('database.connections.mysql.prefix', $prefix);
        
        // Reconnect to apply new prefix
        DB::purge('mysql');
        DB::reconnect('mysql');
        
        return $school;
    }
    
    /**
     * Switch back to main database (no prefix)
     * 
     * @return void
     */
    public static function switchToMain()
    {
        // Reset to no prefix for main database
        Config::set('database.connections.mysql.prefix', '');
        
        // Reconnect to apply changes
        DB::purge('mysql');
        DB::reconnect('mysql');
    }
    
    /**
     * Create tenant tables with prefix
     * 
     * @param int $schoolId
     * @return void
     */
    public static function createTenantTables($schoolId)
    {
        self::switchToTenant($schoolId);
        
        // Create tenant-specific tables if they don't exist
        $tables = [
            'users' => 'CREATE TABLE IF NOT EXISTS users (
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
            
            'classes' => 'CREATE TABLE IF NOT EXISTS classes (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                medium_id BIGINT,
                school_id BIGINT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            )',
            
            'virtual_classrooms' => 'CREATE TABLE IF NOT EXISTS virtual_classrooms (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                class_id BIGINT NOT NULL,
                section_id BIGINT,
                subject_id BIGINT NOT NULL,
                teacher_id BIGINT NOT NULL,
                school_id BIGINT NOT NULL,
                meeting_url VARCHAR(500),
                meeting_id VARCHAR(255),
                password VARCHAR(255),
                status ENUM("scheduled", "live", "completed", "cancelled") DEFAULT "scheduled",
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            )',
            
            'virtual_classroom_attendance' => 'CREATE TABLE IF NOT EXISTS virtual_classroom_attendance (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                virtual_classroom_id BIGINT NOT NULL,
                student_id BIGINT NOT NULL,
                school_id BIGINT NOT NULL,
                join_time DATETIME,
                leave_time DATETIME,
                duration_minutes INT DEFAULT 0,
                status ENUM("present", "absent", "late") DEFAULT "present",
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                DB::statement($sql);
                echo "✅ Created table: {$tableName}\n";
            } catch (\Exception $e) {
                echo "❌ Error creating {$tableName}: " . $e->getMessage() . "\n";
            }
        }
        
        self::switchToMain();
    }
    
    /**
     * Get current table prefix
     * 
     * @return string
     */
    public static function getCurrentPrefix()
    {
        return Config::get('database.connections.mysql.prefix', '');
    }
    
    /**
     * Check if tenant exists
     * 
     * @param int $schoolId
     * @return bool
     */
    public static function tenantExists($schoolId)
    {
        self::switchToTenant($schoolId);
        
        try {
            $exists = DB::getSchemaBuilder()->hasTable('users');
            self::switchToMain();
            return $exists;
        } catch (\Exception $e) {
            self::switchToMain();
            return false;
        }
    }
}
