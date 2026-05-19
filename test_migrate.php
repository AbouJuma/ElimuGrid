<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Running migrations for school 56...\n";
    App\Services\SharedHostingTenantService::switchToTenant(56);
    $shortPrefix = App\Services\SharedHostingTenantService::tenantTablePrefix(56);
    \Illuminate\Support\Facades\Config::set('database.connections.mysql.prefix', $shortPrefix);
    \Illuminate\Support\Facades\DB::purge('mysql');
    \Illuminate\Support\Facades\DB::reconnect('mysql');
    
    echo "Prefix is: " . \Illuminate\Support\Facades\DB::connection('mysql')->getTablePrefix() . "\n";
    
    \Illuminate\Support\Facades\Artisan::call('migrate', [
        '--database' => 'mysql',
        '--path' => 'database/migrations/schools',
        '--force' => true,
    ]);
    echo \Illuminate\Support\Facades\Artisan::output();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
