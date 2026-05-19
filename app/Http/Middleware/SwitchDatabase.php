<?php

namespace App\Http\Middleware;

use App\Services\CachingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Services\SharedHostingTenantService;
use Symfony\Component\HttpFoundation\Response;

class SwitchDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $school_database_name = Session::get('school_database_name');
        if ($school_database_name) {
            SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school_database_name);
            DB::setDefaultConnection('school');
            // Don't check Auth::user() here - let other middleware handle authentication
            return $next($request);
        } else {
            SharedHostingTenantService::resetSchoolDatabaseConnection();
            DB::connection('mysql')->reconnect();
            DB::setDefaultConnection('mysql');
        }

        return $next($request);
    }
}
