<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Seeding roles for school 56...\n";
    $schoolId = 56;
    
    // Switch to tenant DB
    App\Services\SharedHostingTenantService::switchToTenant($schoolId);
    $shortPrefix = App\Services\SharedHostingTenantService::tenantTablePrefix($schoolId);
    \Illuminate\Support\Facades\Config::set('database.connections.mysql.prefix', $shortPrefix);
    \Illuminate\Support\Facades\DB::purge('mysql');
    \Illuminate\Support\Facades\DB::reconnect('mysql');
    
    // Switch default connection to school just in case Spatie uses it
    \Illuminate\Support\Facades\DB::setDefaultConnection('mysql');
    
    $school = \App\Models\School::on('mysql')->find($schoolId);
    $schoolRow = null;

    if (!$school) {
        // School model might use central DB, let's switch to main to fetch school, then back
        App\Services\SharedHostingTenantService::switchToMain();
        $school = \App\Models\School::find($schoolId);
        $schoolRow = (array) \Illuminate\Support\Facades\DB::table('schools')->where('id', $schoolId)->first();
        
        App\Services\SharedHostingTenantService::switchToTenant($schoolId);
        \Illuminate\Support\Facades\Config::set('database.connections.mysql.prefix', $shortPrefix);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');
    } else {
        // If it was found on 'mysql' with prefix, it means it's already in tenant table or prefix wasn't set yet
        // But seed_roles.php sets prefix at line 13.
        // Let's get the row from main anyway to be sure we have full data
        App\Services\SharedHostingTenantService::switchToMain();
        $schoolRow = (array) \Illuminate\Support\Facades\DB::table('schools')->where('id', $schoolId)->first();
        App\Services\SharedHostingTenantService::switchToTenant($schoolId);
        \Illuminate\Support\Facades\Config::set('database.connections.mysql.prefix', $shortPrefix);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');
    }
    
    if (!$school) {
        die("School 56 not found in central database.\n");
    }

    // Ensure school record exists in tenant table (foreign key requirement)
    if ($schoolRow) {
        $tenantSchool = \Illuminate\Support\Facades\DB::table('schools')->where('id', $schoolId)->first();
        if (!$tenantSchool) {
            echo "Syncing school record to tenant table...\n";
            $insertRow = $schoolRow;
            $insertRow['admin_id'] = null; // Set to null first to avoid FK issue with users
            
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('schools');
            $filteredRow = array_intersect_key($insertRow, array_flip($columns));
            
            try {
                \Illuminate\Support\Facades\DB::table('schools')->insert($filteredRow);
            } catch (\Exception $e) {
                echo "Error syncing school: " . $e->getMessage() . "\n";
            }
        }
        
        // Sync Admin User
        $adminId = $schoolRow['admin_id'];
        if ($adminId) {
            $tenantUser = \Illuminate\Support\Facades\DB::table('users')->where('id', $adminId)->first();
            if (!$tenantUser) {
                echo "Syncing admin user (ID: $adminId) to tenant table...\n";
                // Switch to main to get user data
                App\Services\SharedHostingTenantService::switchToMain();
                $userRow = (array) \Illuminate\Support\Facades\DB::table('users')->where('id', $adminId)->first();
                App\Services\SharedHostingTenantService::switchToTenant($schoolId);
                \Illuminate\Support\Facades\Config::set('database.connections.mysql.prefix', $shortPrefix);
                \Illuminate\Support\Facades\DB::purge('mysql');
                \Illuminate\Support\Facades\DB::reconnect('mysql');
                
                if ($userRow) {
                    $userColumns = \Illuminate\Support\Facades\Schema::getColumnListing('users');
                    $filteredUserRow = array_intersect_key($userRow, array_flip($userColumns));
                    try {
                        \Illuminate\Support\Facades\DB::table('users')->insert($filteredUserRow);
                    } catch (\Exception $e) {
                        echo "Error syncing admin user: " . $e->getMessage() . "\n";
                    }
                }
            }
            
            // Link admin_id back to school
            \Illuminate\Support\Facades\DB::table('schools')->where('id', $schoolId)->update(['admin_id' => $adminId]);
        }
    }

    // Clear Spatie permission cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $service = new \App\Services\SchoolDataService();
    $service->createPreSetupRole($school);
    
    echo "Roles seeded successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
