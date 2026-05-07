<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSchoolLibraryTablesSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Found " . $schools->count() . " schools\n";
        
        foreach ($schools as $school) {
            if (!$school->database_name) {
                echo "School {$school->name} has no database_name, skipping.\n";
                continue;
            }
            
            echo "\n=== Processing school: {$school->name} (DB: {$school->database_name}) ===\n";
            
            try {
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');
                
                $schema = DB::connection('school')->getSchemaBuilder();
                
                // Create books table
                if (!$schema->hasTable('books')) {
                    DB::connection('school')->statement("
                        CREATE TABLE `books` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `title` varchar(255) NOT NULL,
                            `author` varchar(255) NOT NULL,
                            `isbn` varchar(50) DEFAULT NULL,
                            `category` varchar(100) DEFAULT NULL,
                            `publisher` varchar(255) DEFAULT NULL,
                            `edition` varchar(100) DEFAULT NULL,
                            `quantity` int NOT NULL DEFAULT 1,
                            `available_quantity` int NOT NULL DEFAULT 1,
                            `rack_number` varchar(50) DEFAULT NULL,
                            `description` text DEFAULT NULL,
                            `school_id` bigint unsigned DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `books_school_id_index` (`school_id`),
                            KEY `books_category_index` (`category`),
                            KEY `books_isbn_index` (`isbn`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    echo "  Created books table\n";
                } else {
                    echo "  books table already exists\n";
                }
                
                // Create book_issues table
                if (!$schema->hasTable('book_issues')) {
                    DB::connection('school')->statement("
                        CREATE TABLE `book_issues` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `book_id` bigint unsigned NOT NULL,
                            `student_id` bigint unsigned DEFAULT NULL,
                            `class_id` bigint unsigned DEFAULT NULL,
                            `issue_date` date NOT NULL,
                            `return_date` date DEFAULT NULL,
                            `actual_return_date` date DEFAULT NULL,
                            `late_days` int DEFAULT 0,
                            `fine_amount` decimal(10,2) DEFAULT 0.00,
                            `status` tinyint NOT NULL DEFAULT 1 COMMENT '1=issued,2=returned,3=overdue',
                            `school_id` bigint unsigned DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `book_issues_book_id_index` (`book_id`),
                            KEY `book_issues_student_id_index` (`student_id`),
                            KEY `book_issues_class_id_index` (`class_id`),
                            KEY `book_issues_school_id_index` (`school_id`),
                            KEY `book_issues_status_index` (`status`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    echo "  Created book_issues table\n";
                } else {
                    echo "  book_issues table already exists\n";
                }
                
            } catch (\Exception $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
            }
        }
        
        // Switch back to main database
        DB::setDefaultConnection('mysql');
        Config::set('database.connections.school.database', '');
        DB::purge('school');
        DB::connection('mysql')->reconnect();
        DB::setDefaultConnection('mysql');
        
        echo "\n=== Done! ===\n";
    }
}
