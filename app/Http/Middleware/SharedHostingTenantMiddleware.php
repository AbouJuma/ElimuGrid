<?php

namespace App\Http\Middleware;

use App\Services\SharedHostingTenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SharedHostingTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip for main routes and guests
        if ($request->is('admin/*') || !Auth::check()) {
            return $next($request);
        }

        try {
            $user = Auth::user();
            
            // Only apply for school users (not super admin)
            if ($user && $user->school_id) {
                // Switch to tenant database using prefix
                SharedHostingTenantService::switchToTenant($user->school_id);
            }
        } catch (\Exception $e) {
            // If tenant switching fails, continue with main database
            // This prevents breaking the entire application
            \Log::error('Tenant switching failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}
