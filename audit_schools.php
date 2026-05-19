<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$schools = DB::table('schools')->get();
foreach ($schools as $school) {
    if (!$school->database_name || !App\Services\SharedHostingTenantService::usesPrefixedTenantTables($school->database_name)) {
        echo "Skipping school {$school->id} ({$school->name}) - not prefixed multi-tenant\n";
        continue;
    }

    echo "Checking school {$school->id} ({$school->name}) with prefix {$school->database_name}...\n";
    
    try {
        App\Services\SharedHostingTenantService::switchToTenant($school->id);
        
        $tenantSchool = DB::table('schools')->where('id', $school->id)->first();
        if (!$tenantSchool) {
            echo "  [MISSING] School record missing in tenant table!\n";
        } else {
            echo "  [OK] School record exists.\n";
            if ($tenantSchool->admin_id != $school->admin_id) {
                 echo "  [MISMATCH] Admin ID mismatch: Central={$school->admin_id}, Tenant={$tenantSchool->admin_id}\n";
            }
        }
        
        $tenantAdmin = DB::table('users')->where('id', $school->admin_id)->first();
        if (!$tenantAdmin) {
            echo "  [MISSING] Admin user missing in tenant table!\n";
        } else {
            echo "  [OK] Admin user exists.\n";
        }
        
        // Check roles
        $rolesCount = DB::table('roles')->count();
        echo "  [INFO] Roles count: $rolesCount\n";
        
        App\Services\SharedHostingTenantService::switchToMain();
    } catch (\Exception $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
        App\Services\SharedHostingTenantService::switchToMain();
    }
}
