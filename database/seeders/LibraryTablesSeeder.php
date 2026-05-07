<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LibraryTablesSeeder extends Seeder
{
    public function run()
    {
        // Create books table if not exists
        if (!Schema::hasTable('books')) {
            DB::statement("
                CREATE TABLE books (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    author VARCHAR(255) NOT NULL,
                    isbn VARCHAR(50) UNIQUE,
                    category VARCHAR(100),
                    quantity INT DEFAULT 0,
                    available_quantity INT DEFAULT 0,
                    school_id BIGINT UNSIGNED,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    deleted_at TIMESTAMP NULL DEFAULT NULL,
                    INDEX idx_school_id (school_id),
                    INDEX idx_category (category),
                    INDEX idx_isbn (isbn)
                )
            ");
        }

        // Create book_issues table if not exists
        if (!Schema::hasTable('book_issues')) {
            DB::statement("
                CREATE TABLE book_issues (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    book_id BIGINT UNSIGNED NOT NULL,
                    student_id BIGINT UNSIGNED NOT NULL,
                    class_id BIGINT UNSIGNED NOT NULL,
                    issue_date DATE NOT NULL,
                    return_date DATE NOT NULL,
                    actual_return_date DATE NULL,
                    late_days INT DEFAULT 0,
                    fine_amount DECIMAL(10,2) DEFAULT 0.00,
                    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
                    school_id BIGINT UNSIGNED,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_book_id (book_id),
                    INDEX idx_student_id (student_id),
                    INDEX idx_class_id (class_id),
                    INDEX idx_school_id (school_id),
                    INDEX idx_status (status)
                )
            ");
        }

        // Mark migrations as run
        $now = now();
        $migrations = [
            '2025_05_10_100000_create_books_table',
            '2025_05_10_100001_create_book_issues_table',
            '2025_05_10_100002_add_library_settings',
        ];

        foreach ($migrations as $migration) {
            $exists = DB::table('migrations')->where('migration', $migration)->first();
            if (!$exists) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => DB::table('migrations')->max('batch') + 1,
                ]);
            }
        }
    }
}
