<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Transport Module Diagnostics ===\n\n";

// 1. Check main database feature
$feature = DB::connection('mysql')->table('features')->where('name', 'Transport Management')->first();
if ($feature) {
    echo "✓ Feature exists: ID {$feature->id}\n";
} else {
    echo "✗ Feature NOT found\n";
}

// 2. Check package features
$pkgFeatures = DB::connection('mysql')->table('package_features')->where('feature_id', 23)->pluck('package_id')->toArray();
echo "✓ Feature in packages: " . implode(', ', $pkgFeatures) . "\n";

// 3. Check subscriptions
$subs = DB::connection('mysql')->table('subscription_features')->where('feature_id', 23)->count();
echo "✓ Feature in subscriptions: {$subs}\n";

// 4. Check schools
$schools = DB::connection('mysql')->table('schools')->get();
echo "\n=== Checking School Databases ===\n";

foreach ($schools as $school) {
    echo "\nSchool: {$school->name} ({$school->database_name})\n";
    
    try {
        Config::set('database.connections.school.database', $school->database_name);
        DB::purge('school');
        
        // Check permissions
        $perms = DB::connection('school')->table('permissions')->where('name', 'like', 'transport%')->count();
        echo "  Transport permissions: {$perms}\n";
        
        // Check role permissions
        $adminRole = DB::connection('school')->table('roles')->where('name', 'School Admin')->first();
        if ($adminRole) {
            $rolePerms = DB::connection('school')->table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->where('role_has_permissions.role_id', $adminRole->id)
                ->where('permissions.name', 'like', 'transport%')
                ->count();
            echo "  Admin role transport perms: {$rolePerms}\n";
        }
        
        // Check features table
        $schoolFeature = DB::connection('school')->table('features')->where('name', 'Transport Management')->first();
        if ($schoolFeature) {
            echo "  ✓ Feature flag exists\n";
        } else {
            echo "  ✗ Feature flag MISSING\n";
        }
    } catch (Exception $e) {
        echo "  Error: {$e->getMessage()}\n";
    }
}

echo "\n=== Done ===\n";
