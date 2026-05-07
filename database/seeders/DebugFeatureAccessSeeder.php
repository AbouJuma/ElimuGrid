<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DebugFeatureAccessSeeder extends Seeder
{
    public function run()
    {
        // Check the school for user aboukisiaki@gmail.com in main DB
        $user = DB::connection('mysql')->table('users')->where('email', 'aboukisiaki@gmail.com')->first();
        if (!$user) {
            echo "User not found!\n";
            return;
        }
        
        echo "User ID: {$user->id}, school_id: " . ($user->school_id ?? 'NULL') . "\n";
        
        if (!$user->school_id) {
            echo "User has no school_id!\n";
            return;
        }
        
        $school = DB::connection('mysql')->table('schools')->where('id', $user->school_id)->first();
        if (!$school) {
            echo "School not found!\n";
            return;
        }
        
        echo "School: {$school->name} (ID: {$school->id})\n";
        
        // Check Library feature
        $libraryFeature = DB::connection('mysql')->table('features')->where('name', 'Library Management')->first();
        if ($libraryFeature) {
            echo "Library Management feature ID: {$libraryFeature->id}, status: {$libraryFeature->status}\n";
        } else {
            echo "Library Management feature NOT FOUND in features table!\n";
        }
        
        // Check active subscription
        $today = now()->format('Y-m-d');
        $sub = DB::connection('mysql')->table('subscriptions')
            ->where('school_id', $school->id)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->latest()
            ->first();
        
        if (!$sub) {
            echo "No active subscription found!\n";
            
            // Check any subscription
            $anySub = DB::connection('mysql')->table('subscriptions')
                ->where('school_id', $school->id)
                ->latest()->first();
            if ($anySub) {
                echo "Last subscription: ID={$anySub->id}, start={$anySub->start_date}, end={$anySub->end_date}\n";
            }
            return;
        }
        
        echo "Active subscription ID: {$sub->id}, Package ID: {$sub->package_id}, Type: {$sub->package_type}\n";
        echo "Start: {$sub->start_date}, End: {$sub->end_date}\n";
        
        // Check subscription features
        $subFeatures = DB::connection('mysql')->table('subscription_features')
            ->where('subscription_id', $sub->id)->get();
        echo "\nSubscription features (" . $subFeatures->count() . "):\n";
        foreach ($subFeatures as $sf) {
            $feature = DB::connection('mysql')->table('features')->where('id', $sf->feature_id)->first();
            echo "  - Feature ID: {$sf->feature_id} = " . ($feature ? $feature->name : 'NOT FOUND') . "\n";
        }
        
        // Check package features
        $pkgFeatures = DB::connection('mysql')->table('package_features')
            ->where('package_id', $sub->package_id)->get();
        echo "\nPackage features (" . $pkgFeatures->count() . "):\n";
        foreach ($pkgFeatures as $pf) {
            $feature = DB::connection('mysql')->table('features')->where('id', $pf->feature_id)->first();
            echo "  - Feature ID: {$pf->feature_id} = " . ($feature ? $feature->name : 'NOT FOUND') . "\n";
        }
        
        // Check subscription bills
        $bills = DB::connection('mysql')->table('subscription_bills')
            ->where('subscription_id', $sub->id)->get();
        echo "\nSubscription bills (" . $bills->count() . "):\n";
        foreach ($bills as $bill) {
            echo "  - Bill ID: {$bill->id}, amount: {$bill->amount}\n";
            $transaction = DB::connection('mysql')->table('payment_transactions')
                ->where('id', $bill->payment_transaction_id)->first();
            if ($transaction) {
                echo "    Payment status: {$transaction->payment_status}\n";
            }
        }
    }
}
