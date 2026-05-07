<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Feature Access ===\n\n";

// Test with school ID 3 (which should have the feature)
$schoolId = 3;

// Clear cache first
$cacheKey = "features_{$schoolId}";
Cache::forget($cacheKey);
echo "Cleared cache: {$cacheKey}\n";

// Get active subscription
$subService = app(\App\Services\SubscriptionService::class);
$activeSub = $subService->active_subscription($schoolId);

if ($activeSub) {
    echo "Active subscription found: ID {$activeSub->id}\n";
    
    // Check subscription features
    $features = $activeSub->subscription_feature;
    echo "Subscription features count: " . ($features ? $features->count() : 0) . "\n";
    
    if ($features) {
        $featureNames = $features->pluck('feature.name', 'feature.id')->toArray();
        echo "Features:\n";
        foreach ($featureNames as $id => $name) {
            echo "  - {$name} (ID: {$id})\n";
        }
        
        if (in_array('Transport Management', $featureNames)) {
            echo "\n✓ Transport Management IS in subscription features!\n";
        } else {
            echo "\n✗ Transport Management NOT found in subscription features\n";
        }
    }
} else {
    echo "No active subscription found!\n";
}

// Check if there's an outstanding bill
$today = Carbon\Carbon::now()->format('Y-m-d');
$bill = \App\Models\SubscriptionBill::with(['subscription' => function($q) {
    $q->where('package_type', 1);
}])
->where('school_id', $schoolId)
->whereHas('transaction', function($q) {
    $q->whereNot('payment_status', 'succeed');
})
->where('due_date', '<', $today)
->first();

if ($bill) {
    echo "\n⚠ WARNING: Outstanding subscription bill found!\n";
    echo "  Due date: {$bill->due_date}\n";
    echo "  This will block feature access!\n";
} else {
    echo "\n✓ No outstanding bills\n";
}

echo "\n=== Done ===\n";
