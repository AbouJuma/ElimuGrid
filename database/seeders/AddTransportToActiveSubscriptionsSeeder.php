<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AddTransportToActiveSubscriptionsSeeder extends Seeder
{
    public function run()
    {
        echo "=== Adding Transport Management to Active Subscriptions ===\n\n";

        // Get Transport Management feature
        $feature = DB::connection('mysql')->table('features')
            ->where('name', 'Transport Management')
            ->first();

        if (!$feature) {
            echo "ERROR: Transport Management feature not found!\n";
            return;
        }

        $featureId = $feature->id;

        // Get active subscriptions
        $subscriptions = DB::connection('mysql')->table('subscriptions')
            ->where('end_date', '>=', now())
            ->get();

        echo "Found {$subscriptions->count()} active subscriptions\n\n";

        $added = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            // Check if already has Transport Management
            $exists = DB::connection('mysql')->table('subscription_features')
                ->where('subscription_id', $subscription->id)
                ->where('feature_id', $featureId)
                ->first();

            if (!$exists) {
                DB::connection('mysql')->table('subscription_features')->insert([
                    'subscription_id' => $subscription->id,
                    'feature_id' => $featureId,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                echo "✓ Added to subscription ID: {$subscription->id} (School: {$subscription->school_id})\n";
                $added++;
            } else {
                $skipped++;
            }
        }

        echo "\n=== Summary ===\n";
        echo "Added: {$added}\n";
        echo "Already had: {$skipped}\n";
        echo "\nNow clearing feature caches...\n";

        // Clear all school feature caches
        $this->clearFeatureCaches($subscriptions->pluck('school_id')->toArray());

        echo "\n✓ Done! Transport Management is now active for all subscriptions.\n";
    }

    private function clearFeatureCaches(array $schoolIds)
    {
        try {
            foreach ($schoolIds as $schoolId) {
                $cacheKey = "features_{$schoolId}";
                if (\Cache::has($cacheKey)) {
                    \Cache::forget($cacheKey);
                    echo "  ✓ Cleared cache for school {$schoolId}\n";
                }
            }
        } catch (\Exception $e) {
            echo "  ⚠ Cache clear warning: {$e->getMessage()}\n";
        }
    }
}
