<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMissingTablesSeeder extends Seeder
{
    public function run()
    {
        // Create mediums table if not exists
        if (!Schema::hasTable('mediums')) {
            DB::statement("
                CREATE TABLE IF NOT EXISTS mediums (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
        }

        // Create shifts table if not exists
        if (!Schema::hasTable('shifts')) {
            DB::statement("
                CREATE TABLE IF NOT EXISTS shifts (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    start_time TIME NULL,
                    end_time TIME NULL,
                    status TINYINT DEFAULT 1,
                    school_id BIGINT UNSIGNED,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_school_id (school_id)
                )
            ");
        }

        // Create streams table if not exists
        if (!Schema::hasTable('streams')) {
            DB::statement("
                CREATE TABLE IF NOT EXISTS streams (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
        }

        // Create classes table if not exists
        if (!Schema::hasTable('classes')) {
            DB::statement("
                CREATE TABLE IF NOT EXISTS classes (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(512) NOT NULL,
                    include_semesters TINYINT DEFAULT 0 COMMENT '0 - no 1 - yes',
                    medium_id BIGINT UNSIGNED,
                    shift_id BIGINT UNSIGNED,
                    stream_id BIGINT UNSIGNED,
                    school_id BIGINT UNSIGNED,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    deleted_at TIMESTAMP NULL DEFAULT NULL,
                    INDEX idx_school_id (school_id),
                    INDEX idx_medium_id (medium_id),
                    INDEX idx_shift_id (shift_id),
                    INDEX idx_stream_id (stream_id)
                )
            ");
        }

        // Mark all_tables migration as run if not already
        $migration = '2022_04_01_105826_all_tables';
        $exists = DB::table('migrations')->where('migration', $migration)->first();
        if (!$exists) {
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => DB::table('migrations')->max('batch') + 1,
            ]);
        }

        echo "Missing tables created successfully.\n";
    }
}
