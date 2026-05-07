<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Clearing All Caches ===\n\n";

// Clear all schools' feature caches
$schools = DB::connection('mysql')->table('schools')->get();

foreach ($schools as $school) {
    $cacheKey = "school_features_{$school->id}";
    if (Cache::has($cacheKey)) {
        Cache::forget($cacheKey);
        echo "✓ Cleared features cache for school {$school->id}: {$school->name}\n";
    }
    
    $cacheKey2 = "features_{$school->id}";
    if (Cache::has($cacheKey2)) {
        Cache::forget($cacheKey2);
        echo "✓ Cleared features cache (alt) for school {$school->id}\n";
    }
}

// Clear general caches
cache()->flush();
echo "✓ Flushed general cache\n";

// Clear view cache
Artisan::call('view:clear');
echo "✓ Cleared view cache\n";

echo "\n=== Done ===\n";
