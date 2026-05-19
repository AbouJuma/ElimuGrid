<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SharedHostingTenantService
{
    /**
     * Table prefix for a school's isolated tables on the shared database (e.g. s12_).
     */
    public static function tenantTablePrefix(int $schoolId): string
    {
        return 's' . $schoolId . '_';
    }

    /**
     * True when schools.database_name uses the shared-hosting prefix pattern (no separate schema).
     */
    public static function usesPrefixedTenantTables(?string $databaseName): bool
    {
        return $databaseName !== null && (preg_match('/^s\d+_$/', $databaseName) === 1 || preg_match('/^school_\d+_$/', $databaseName) === 1);
    }

    /**
     * School id encoded in schools.database_name for shared-hosting tenants (e.g. s12_ -> 12).
     */
    public static function schoolIdFromPrefixedDatabaseName(string $databaseName): ?int
    {
        if (preg_match('/^s(\d+)_$/', $databaseName, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/^school_(\d+)_$/', $databaseName, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Switch context to a specific tenant (school).
     */
    public static function switchToTenant(int $schoolId): void
    {
        $prefix = self::tenantTablePrefix($schoolId);
        
        // DO NOT set prefix on 'mysql' connection. 
        // Global models (e.g. School, Package) use 'mysql' and must remain unprefixed.
        Config::set('tenant.current_school_id', $schoolId);
        
        // Configure 'school' connection to use the tenant database
        self::configureSchoolConnectionFromDatabaseName($prefix);

        // Update Spatie table names to use prefix
        Log::info("Switching Spatie tables for School $schoolId with prefix $prefix");
        config([
            'permission.table_names.roles' => $prefix . 'roles',
            'permission.table_names.permissions' => $prefix . 'permissions',
            'permission.table_names.model_has_permissions' => $prefix . 'model_has_permissions',
            'permission.table_names.model_has_roles' => $prefix . 'model_has_roles',
            'permission.table_names.role_has_permissions' => $prefix . 'role_has_permissions',
        ]);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if (Auth::check()) {
            Log::info("Clearing relations for user " . Auth::id());
            Auth::user()->unsetRelation('roles');
            Auth::user()->unsetRelation('permissions');
        }

        // For legacy code and models without a connection property, 
        // set 'school' as the default connection.
        DB::setDefaultConnection('school');
    }

    /**
     * Switch back to the main (central) database context.
     */
    public static function switchToMain(): void
    {
        Config::set('tenant.current_school_id', null);
        
        // Reset Spatie table names
        config([
            'permission.table_names.roles' => 'roles',
            'permission.table_names.permissions' => 'permissions',
            'permission.table_names.model_has_permissions' => 'model_has_permissions',
            'permission.table_names.model_has_roles' => 'model_has_roles',
            'permission.table_names.role_has_permissions' => 'role_has_permissions',
        ]);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Reset 'school' connection to main database
        self::resetSchoolDatabaseConnection();

        // Switch default back to mysql
        DB::setDefaultConnection('mysql');
    }

    /**
     * Point the `school` connection at the correct tenant.
     * Prefixed tenants: same MySQL database as `mysql`, with table prefix s{id}_.
     * Legacy: separate schema name on `school` connection, no prefix.
     */
    public static function configureSchoolConnectionFromDatabaseName(?string $databaseName): void
    {
        Log::info("configureSchoolConnectionFromDatabaseName called with: '" . ($databaseName ?? 'NULL') . "'");
        $mysql = config('database.connections.mysql');
        $centralDb = $mysql['database'];

        if ($databaseName === null || $databaseName === '') {
            self::resetSchoolDatabaseConnection();
            return;
        }

        if (self::usesPrefixedTenantTables($databaseName)) {
            $schoolId = self::schoolIdFromPrefixedDatabaseName($databaseName);
            if ($schoolId === null) {
                return;
            }

            foreach (['driver', 'url', 'host', 'port', 'database', 'username', 'password', 'unix_socket', 'charset', 'collation', 'strict', 'engine'] as $key) {
                if (array_key_exists($key, $mysql)) {
                    Config::set("database.connections.school.{$key}", $mysql[$key]);
                }
            }
            if (! empty($mysql['options']) && is_array($mysql['options'])) {
                Config::set('database.connections.school.options', $mysql['options']);
            }
            Config::set('database.connections.school.database', $centralDb);
            Config::set('database.connections.school.prefix', '');
            Config::set('database.connections.school.prefix_indexes', true);
            Log::info("Configured 'school' connection for tenant prefix: " . $databaseName);
            DB::purge('school');
            DB::reconnect('school');

            return;
        }

        Config::set('database.connections.school.prefix', '');
        Config::set('database.connections.school.prefix_indexes', true);
        Config::set('database.connections.school.database', $databaseName);
        DB::purge('school');
        DB::reconnect('school');
    }

    /**
     * Clear `school` connection tenant targeting (prefix + database name).
     * Points it back to central database with NO prefix.
     */
    public static function resetSchoolDatabaseConnection(): void
    {
        $centralDb = config('database.connections.mysql.database');
        Config::set('database.connections.school.prefix', '');
        Config::set('database.connections.school.prefix_indexes', true);
        Config::set('database.connections.school.database', $centralDb);
        DB::purge('school');
        DB::reconnect('school');

        // Also reset mysql connection prefix which might have been set during migrations
        Config::set('database.connections.mysql.prefix', '');
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    /**
     * Create tenant tables with prefix using migrations
     * This works on shared hosting without CREATE DATABASE permissions
     * 
     * @param int $schoolId
     * @return void
     */
    public static function createTenantTables($schoolId)
    {
        try {
            self::switchToTenant($schoolId);
            
            // Create basic infrastructure tables first (needed for foreign keys)
            self::createBasicTenantTables($schoolId);

            // For migrations, we MUST set the prefix on the connection
            $prefix = self::tenantTablePrefix($schoolId);
            Config::set('database.connections.school.prefix', $prefix);
            DB::purge('school');
            DB::reconnect('school');

            // Temporarily reset Spatie config to unprefixed names for the migration
            // because the connection prefix will already handle it.
            config([
                'permission.table_names.roles' => 'roles',
                'permission.table_names.permissions' => 'permissions',
                'permission.table_names.model_has_permissions' => 'model_has_permissions',
                'permission.table_names.model_has_roles' => 'model_has_roles',
                'permission.table_names.role_has_permissions' => 'role_has_permissions',
            ]);

            $exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--path' => 'database/migrations/schools',
                '--database' => 'school',
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                $output = \Illuminate\Support\Facades\Artisan::output();
                throw new \Exception("Migration failed with exit code $exitCode: " . $output);
            }
            Log::info("Tenant migrations completed for school {$schoolId}");
            
            self::switchToMain();
            
        } catch (\Exception $e) {
            self::switchToMain();
            Log::error("Tenant migration failed for school {$schoolId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create all necessary tables for a tenant.
     * 
     * @param int $schoolId
     * @return void
     */
    public static function createBasicTenantTables($schoolId)
    {
        $tables = [
            'schools' => 'CREATE TABLE IF NOT EXISTS schools (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(191) NOT NULL,
                address VARCHAR(191),
                support_phone VARCHAR(191),
                support_email VARCHAR(191),
                tagline VARCHAR(191),
                logo VARCHAR(191),
                admin_id BIGINT UNSIGNED,
                status TINYINT(4) DEFAULT 0,
                domain VARCHAR(191),
                database_name VARCHAR(191),
                code VARCHAR(191),
                domain_type VARCHAR(191),
                type VARCHAR(191),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL
            ) ENGINE=InnoDB',
            
            'users' => 'CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                mobile VARCHAR(20),
                image VARCHAR(255),
                password VARCHAR(255),
                current_address VARCHAR(255),
                permanent_address VARCHAR(255),
                gender VARCHAR(20),
                dob DATE,
                occupation VARCHAR(255),
                status TINYINT(1) DEFAULT 1,
                reset_request TINYINT(1) DEFAULT 0,
                fcm_id VARCHAR(255),
                remember_token VARCHAR(100),
                email_verified_at TIMESTAMP NULL,
                two_factor_secret VARCHAR(255),
                two_factor_expires_at TIMESTAMP NULL,
                two_factor_enabled TINYINT(1) DEFAULT 0,
                school_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
            ) ENGINE=InnoDB',
            
            'classes' => 'CREATE TABLE IF NOT EXISTS classes (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                include_semesters TINYINT(1) DEFAULT 0,
                medium_id BIGINT UNSIGNED,
                shift_id BIGINT UNSIGNED,
                stream_id BIGINT UNSIGNED,
                school_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
            ) ENGINE=InnoDB',

            'session_years' => 'CREATE TABLE IF NOT EXISTS session_years (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(191) NOT NULL,
                start_date DATE,
                end_date DATE,
                status TINYINT(1) DEFAULT 1,
                default TINYINT(1) DEFAULT 0,
                school_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        ];
        
        $prefix = self::tenantTablePrefix($schoolId);
        foreach ($tables as $tableName => $sql) {
            try {
                // Manually apply prefix to raw SQL as DB::statement does not do it automatically
                $prefixedSql = str_replace('CREATE TABLE IF NOT EXISTS ', 'CREATE TABLE IF NOT EXISTS ' . $prefix, $sql);
                $prefixedSql = str_replace('REFERENCES ', 'REFERENCES ' . $prefix, $prefixedSql);
                
                DB::statement($prefixedSql);
                Log::debug("Created tenant table: {$prefix}{$tableName}");
            } catch (\Exception $e) {
                Log::error("Error creating tenant table {$tableName}: " . $e->getMessage());
            }
        }
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
     * Get current school ID
     * 
     * @return int|null
     */
    public static function getCurrentSchoolId()
    {
        return Config::get('tenant.current_school_id');
    }
    
    /**
     * Check if tenant exists (has basic tables)
     * 
     * @param int $schoolId
     * @return bool
     */
    public static function tenantExists($schoolId)
    {
        try {
            self::switchToTenant($schoolId);
            
            // Check if users table exists for this tenant
            $exists = Schema::hasTable('users');
            
            self::switchToMain();
            return $exists;
            
        } catch (\Exception $e) {
            self::switchToMain();
            return false;
        }
    }
    
    /**
     * Drop all tenant tables (for school deletion)
     * 
     * @param int $schoolId
     * @return void
     */
    public static function dropTenantTables($schoolId)
    {
        try {
            // Switch to main to drop prefixed tables
            self::switchToMain();
            
            $prefix = self::tenantTablePrefix($schoolId);
            $tables = DB::select('SHOW TABLES LIKE "' . $prefix . '%"');
            
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                DB::statement('DROP TABLE IF EXISTS `' . $tableName . '`');
                Log::debug("Dropped tenant table: {$tableName}");
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            throw $e;
        }
    }
    
    /**
     * Run tenant seeder
     * 
     * @param int $schoolId
     * @param string $seederClass
     * @return void
     */
    public static function runTenantSeeder($schoolId, $seederClass)
    {
        try {
            self::switchToTenant($schoolId);
            
            \Artisan::call('db:seed', [
                '--class' => $seederClass,
                '--force' => true
            ]);
            
            self::switchToMain();
            
        } catch (\Exception $e) {
            self::switchToMain();
            throw $e;
        }
    }
}
