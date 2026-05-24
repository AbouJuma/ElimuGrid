<?php

namespace App\Traits;

use App\Services\SharedHostingTenantService;

trait TenantModel
{
    public function getConnectionName()
    {
        // Force multi-tenant models to stay on the same connection as the User model (mysql)
        // This allows cross-model queries (joins, whereHas) to work correctly
        // while still maintaining isolation through dynamic table prefixes.
        return 'mysql';
    }

    public static function resolveSchoolIdForTenancy()
    {
        $schoolId = config('tenant.current_school_id');

        if (!$schoolId && class_exists(\Illuminate\Support\Facades\Auth::class)) {
            $user = \Illuminate\Support\Facades\Auth::hasUser() ? \Illuminate\Support\Facades\Auth::user() : null;
            if ($user && $user->school_id) {
                $schoolId = $user->school_id;
            }
        }

        if (!$schoolId) {
            $schoolDb = config('database.connections.school.database');
            if ($schoolDb) {
                if (preg_match('/^s(\d+)_$/', $schoolDb, $matches)) {
                    $schoolId = (int)$matches[1];
                } elseif (preg_match('/^school_(\d+)_$/', $schoolDb, $matches)) {
                    $schoolId = (int)$matches[1];
                }
            }
        }

        return $schoolId;
    }

    public function getTable()
    {
        $baseTable = parent::getTable();
        
        static $resolving = false;
        if ($resolving) {
            return $baseTable;
        }

        $resolving = true;
        try {
            // If the connection already has a prefix, don't add it again
            $connection = $this->getConnection();
            $connectionPrefix = $connection->getConfig('prefix');
            if ($connectionPrefix) {
                return $baseTable;
            }

            $schoolId = self::resolveSchoolIdForTenancy();

            file_put_contents(
                storage_path('logs/tenant_debug.log'),
                "[" . date('Y-m-d H:i:s') . "] Model: " . get_class($this) . " | schoolId: " . ($schoolId ?? 'NULL') . " | schoolDb: " . (config('database.connections.school.database') ?? 'NULL') . " | hasUser: " . (class_exists(\Illuminate\Support\Facades\Auth::class) && \Illuminate\Support\Facades\Auth::hasUser() ? 'YES' : 'NO') . " | URL: " . (request() ? request()->fullUrl() : 'CLI') . "\n",
                FILE_APPEND
            );

            if ($schoolId) {
                $prefix = 's' . $schoolId . '_';
                if (!str_starts_with($baseTable, $prefix)) {
                    return $prefix . $baseTable;
                }
            }
        } finally {
            $resolving = false;
        }
        return $baseTable;
    }

    public function newEloquentBuilder($query)
    {
        return new \App\Builders\TenantEloquentBuilder($query);
    }
}
