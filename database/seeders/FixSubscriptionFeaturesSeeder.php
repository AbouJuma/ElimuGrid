<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixSubscriptionFeaturesSeeder extends Seeder
{
    public function run()
    {
        // Get all subscriptions
        $subscriptions = DB::connection('mysql')->table('subscriptions')->get();
        
        foreach ($subscriptions as $sub) {
            // Get package features
            $packageFeatures = DB::connection('mysql')->table('package_features')
                ->where('package_id', $sub->package_id)
                ->pluck('feature_id')
                ->toArray();
            
            // Get existing subscription features
            $existingFeatures = DB::connection('mysql')->table('subscription_features')
                ->where('subscription_id', $sub->id)
                ->pluck('feature_id')
                ->toArray();
            
            // Find missing features
            $missingFeatures = array_diff($packageFeatures, $existingFeatures);
            
            if (!empty($missingFeatures)) {
                echo "Subscription ID {$sub->id} (Package {$sub->package_id}): Missing " . count($missingFeatures) . " features\n";
                
                foreach ($missingFeatures as $featureId) {
                    $feature = DB::connection('mysql')->table('features')->where('id', $featureId)->first();
                    echo "  Adding: {$feature->name} (ID: {$featureId})\n";
                    
                    DB::connection('mysql')->table('subscription_features')->insert([
                        'subscription_id' => $sub->id,
                        'feature_id' => $featureId,
                    ]);
                }
            } else {
                echo "Subscription ID {$sub->id}: All features synced\n";
            }
        }
        
        echo "\nDone!\n";
    }
}
