<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AddHostelToActiveSubscriptionsSeeder extends Seeder
{
    public function run()
    {
        echo "=== Adding Hostel Management to Active Subscriptions ===\n\n";
        
        // 1. Get Hostel Management feature ID from main database
        $hostelFeature = DB::table('features')
            ->where('name', 'Hostel Management')
            ->first();
        
        if (!$hostelFeature) {
            echo "ERROR: Hostel Management feature not found in main database!\n";
            return;
        }
        
        $hostelFeatureId = $hostelFeature->id;
        echo "Hostel Management Feature ID: {$hostelFeatureId}\n\n";
        
        // 2. Get all active subscriptions
        $today = Carbon::now()->format('Y-m-d');
        $activeSubscriptions = DB::table('subscriptions')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get();
        
        echo "Found " . $activeSubscriptions->count() . " active subscriptions\n\n";
        
        $updated = 0;
        foreach ($activeSubscriptions as $subscription) {
            echo "Processing Subscription ID: {$subscription->id}, School ID: {$subscription->school_id}\n";
            
            // Check if feature already exists for this subscription
            $exists = DB::table('subscription_features')
                ->where('subscription_id', $subscription->id)
                ->where('feature_id', $hostelFeatureId)
                ->first();
            
            if (!$exists) {
                DB::table('subscription_features')->insert([
                    'subscription_id' => $subscription->id,
                    'feature_id' => $hostelFeatureId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "  ✓ Added Hostel Management to subscription\n";
                $updated++;
            } else {
                echo "  ℹ Hostel Management already exists for this subscription\n";
            }
            
            // Clear cache for this school
            $cacheKey = 'features_' . $subscription->school_id;
            Cache::forget($cacheKey);
            echo "  ✓ Cleared cache: {$cacheKey}\n";
        }
        
        echo "\n=== Summary ===\n";
        echo "Updated {$updated} subscriptions with Hostel Management feature\n";
        echo "All relevant caches cleared\n";
        echo "\nPlease refresh your browser to see the changes.\n";
    }
}
