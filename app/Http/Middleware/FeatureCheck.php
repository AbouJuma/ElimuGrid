<?php

namespace App\Http\Middleware;

use App\Services\FeaturesService;
use Closure;
use Illuminate\Http\Request;

class FeatureCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $feature
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $feature)
    {
        // Check if the feature is enabled for the current school
        if (!FeaturesService::hasFeature($feature)) {
            // If AJAX request, return JSON response
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'This feature is not available in your current subscription package.'
                ], 403);
            }

            // For web requests, redirect with error message
            return redirect()->back()->with('error', 'This feature is not available in your current subscription package.');
        }

        return $next($request);
    }
}
