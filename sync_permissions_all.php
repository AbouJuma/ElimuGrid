<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\School;
use App\Services\SchoolDataService;
use App\Services\SharedHostingTenantService;
use Illuminate\Support\Facades\DB;

try {
    $schools = School::all();
    $service = new SchoolDataService();

    foreach ($schools as $school) {
        echo "Syncing permissions for school: {$school->name} (ID: {$school->id})...\n";
        
        try {
            // Switch to tenant context
            SharedHostingTenantService::switchToTenant($school->id);
            
            // Re-run the role and permission setup
            // This will use the updated SchoolDataService logic to sync permissions
            $service->createPreSetupRole($school);
            
            echo "Successfully synced school {$school->id}\n";
        } catch (\Exception $e) {
            echo "Error syncing school {$school->id}: " . $e->getMessage() . "\n";
        }
    }
    
    // Switch back to main
    SharedHostingTenantService::switchToMain();
    DB::setDefaultConnection('mysql');
    
    echo "All schools synced successfully!\n";
} catch (\Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
