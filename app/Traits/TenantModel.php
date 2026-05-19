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

    public function getTable()
    {
        $baseTable = parent::getTable();
        
        // If the connection already has a prefix, don't add it again
        $connection = $this->getConnection();
        $connectionPrefix = $connection->getConfig('prefix');
        if ($connectionPrefix) {
            return $baseTable;
        }

        $schoolId = config('tenant.current_school_id');
        if ($schoolId) {
            $prefix = 's' . $schoolId . '_';
            if (!str_starts_with($baseTable, $prefix)) {
                return $prefix . $baseTable;
            }
        }
        return $baseTable;
    }
}
