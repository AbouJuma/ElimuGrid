<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMissingSchoolTablesSeeder extends Seeder
{
    public function run()
    {
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Creating missing tables in school databases...\n";
        
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
                
                // Create class_schools table
                if (!$schema->hasTable('class_schools')) {
                    DB::connection('school')->statement("
                        CREATE TABLE `class_schools` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `school_id` bigint unsigned DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `class_schools_school_id_index` (`school_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    echo "  Created class_schools table\n";
                } else {
                    echo "  class_schools table already exists\n";
                }
                
                // Create other potentially missing tables
                $additionalTables = [
                    'mediums' => [
                        'sql' => "CREATE TABLE `mediums` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `school_id` bigint unsigned DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `mediums_school_id_index` (`school_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    ],
                    'sections' => [
                        'sql' => "CREATE TABLE `sections` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `school_id` bigint unsigned DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `sections_school_id_index` (`school_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    ],
                    'shifts' => [
                        'sql' => "CREATE TABLE `shifts` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `school_id` bigint unsigned DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `shifts_school_id_index` (`school_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    ],
                    'streams' => [
                        'sql' => "CREATE TABLE `streams` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `school_id` bigint unsigned DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            `deleted_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `streams_school_id_index` (`school_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    ]
                ];
                
                foreach ($additionalTables as $tableName => $tableInfo) {
                    if (!$schema->hasTable($tableName)) {
                        DB::connection('school')->statement($tableInfo['sql']);
                        echo "  Created {$tableName} table\n";
                    } else {
                        echo "  {$tableName} table already exists\n";
                    }
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
