<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\School;
use App\Models\User;
use App\Services\SharedHostingTenantService;
use Illuminate\Support\Facades\DB;

try {
    $schoolId = 56;
    $school = School::find($schoolId);
    
    if (!$school) {
        die("School 56 not found.\n");
    }

    echo "Checking permissions for School Admin of school {$school->name} (ID: {$schoolId})...\n";
    
    // Switch to tenant context
    SharedHostingTenantService::switchToTenant($schoolId);
    
    // Find school admin user in tenant database
    $adminId = $school->admin_id;
    $admin = User::find($adminId);
    
    if (!$admin) {
        echo "Admin user (ID: {$adminId}) not found in tenant database.\n";
        die();
    }

    echo "Admin User: {$admin->full_name} (Email: {$admin->email})\n";
    echo "Roles: " . implode(', ', $admin->getRoleNames()->toArray()) . "\n";
    
    // Permissions checked by sidebar for Transport
    $checkPermissions = [
        'transport-route-list', 
        'transport-allocation-list', 
        'transport-report-view',
        'hostel-list',
        'room-list'
    ];
    
    foreach ($checkPermissions as $perm) {
        try {
            echo "Has Permission '{$perm}': " . ($admin->hasPermissionTo($perm) ? 'YES' : 'NO') . "\n";
        } catch (\Exception $e) {
            echo "Permission '{$perm}' ERROR: " . $e->getMessage() . "\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
