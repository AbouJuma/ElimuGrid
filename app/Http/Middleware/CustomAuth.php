<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

class CustomAuth
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
        // Check if user is authenticated via custom session
        if (!Session::get('auth_logged_in') || !Session::get('auth_user_id')) {
            if (\Auth::guard('web')->check()) {
                return $next($request);
            }
            return Redirect::route('login')->with('error', 'Please login to continue.');
        }
        
        // Set up database connection for the authenticated user
        $schoolId = Session::get('auth_school_id');
        $schoolDatabase = Session::get('auth_school_database');
        
        if ($schoolId) {
            \App\Services\SharedHostingTenantService::switchToTenant($schoolId);
        } elseif ($schoolDatabase) {
            \App\Services\SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($schoolDatabase);
            \DB::setDefaultConnection('school');
        }
        
        if ($schoolId || $schoolDatabase) {
            // Test the database connection to ensure it's working
            try {
                \DB::connection('school')->table('users')->limit(1)->get();
                \Log::info('Database connection test successful');
            } catch (\Exception $e) {
                \Log::error('Database connection test failed: ' . $e->getMessage());
                // Fallback to default connection
                \DB::setDefaultConnection('mysql');
            }
        }
        
        return $next($request);
    }
}
