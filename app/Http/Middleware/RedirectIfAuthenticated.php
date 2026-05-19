<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Services\SharedHostingTenantService;
use Throwable;

class RedirectIfAuthenticated {
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @param string|null ...$guards
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards) {

        $school_database_name = Session::get('school_database_name');
        if ($school_database_name) {
            SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school_database_name);
            DB::setDefaultConnection('school');
            $guards = empty($guards) ? [null] : $guards;
            
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    return redirect(RouteServiceProvider::HOME);
                }
            }
        } else {
            SharedHostingTenantService::resetSchoolDatabaseConnection();
            DB::setDefaultConnection('mysql');
        }
        
        return $next($request);
        
    }
}
