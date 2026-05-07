<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;

class CreateTestRouteSeeder extends Seeder
{
    public function run()
    {
        // Add a simple test route to verify the controller is working
        Route::get('/test-library-issues', function () {
            return response()->json([
                'message' => 'Test route working',
                'timestamp' => now(),
                'user' => auth()->user() ? auth()->user()->email : 'guest'
            ]);
        });
        
        echo "Added test route: /test-library-issues\n";
        echo "Visit this URL in your browser to test if routes are working\n";
    }
}
