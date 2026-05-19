<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $schoolId = 2;
    DB::table('schools')->where('id', $schoolId)->update(['database_name' => 's2_']);
    $school = DB::table('schools')->find($schoolId);
    $prefix = 's2_';
    echo "Repairing School $schoolId with prefix $prefix...\n";
    
    $beforeTables = DB::select("SHOW TABLES LIKE '$prefix%'");
    echo "Tables before drop: " . count($beforeTables) . "\n";
    
    App\Services\SharedHostingTenantService::dropTenantTables($schoolId);
    
    $afterTables = DB::select("SHOW TABLES LIKE '$prefix%'");
    echo "Tables after drop: " . count($afterTables) . "\n";
    foreach($afterTables as $t) {
        $array = (array)$t;
        echo "Remaining table: " . array_values($array)[0] . "\n";
    }
    
    echo "Tables dropped.\n";
    
    App\Services\SharedHostingTenantService::switchToTenant($schoolId);
    $exitCode = Artisan::call('migrate', ['--path' => 'database/migrations/schools', '--force' => true, '-vvv' => true]);
    file_put_contents('migration_output.txt', Artisan::output());
    echo "Migrations exit code: $exitCode\n";
    
    if ($exitCode !== 0) {
        throw new Exception("Migration failed with exit code $exitCode");
    }
    
    App\Services\SharedHostingTenantService::switchToTenant($schoolId);
    
    // Sync school record
    $schoolRow = (array)$school;
    $columns = Schema::getColumnListing('schools');
    $filteredRow = array_intersect_key($schoolRow, array_flip($columns));
    $filteredRow['admin_id'] = null;
    DB::table('schools')->insert($filteredRow);
    
    $service = new App\Services\SchoolDataService();
    $service->createPreSetupRole($school);
    echo "Roles and permissions seeded.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
