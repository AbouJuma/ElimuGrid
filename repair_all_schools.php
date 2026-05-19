<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\SharedHostingTenantService;
use App\Services\SchoolDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

$schools = DB::table('schools')->get();
$service = new SchoolDataService();

foreach ($schools as $school) {
    echo "Repairing school {$school->id} ({$school->name})...\n";
    
    try {
        // 1. Ensure prefix is set
        $prefix = SharedHostingTenantService::tenantTablePrefix($school->id);
        if ($school->database_name !== $prefix) {
            echo "  Updating database_name to $prefix\n";
            DB::table('schools')->where('id', $school->id)->update(['database_name' => $prefix]);
            $school->database_name = $prefix;
        }

        echo "  Running full tenant provisioning and data sync...\n";
        try {
            // Clear Spatie permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $service->preSettingsSetup($school);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "  Inconsistent migrations detected. Attempting to drop and recreate tenant tables...\n";
                SharedHostingTenantService::dropTenantTables($school->id);
                $service->preSettingsSetup($school);
            } else {
                throw $e;
            }
        }
        
        echo "  [SUCCESS] School {$school->id} repaired and provisioned.\n";
        
        SharedHostingTenantService::switchToMain();
    } catch (\Exception $e) {
        echo "  [FAILED] " . $e->getMessage() . "\n";
        SharedHostingTenantService::switchToMain();
    }
}
echo "All schools processed.\n";
