<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Services\SharedHostingTenantService;

class CheckRole {
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next) {
        $school_database_name = Session::get('school_database_name');
        if ($school_database_name) {
            SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school_database_name);
            DB::setDefaultConnection('school');
        } else {
            SharedHostingTenantService::resetSchoolDatabaseConnection();
            DB::connection('mysql')->reconnect();
            DB::setDefaultConnection('mysql');
        }

        if (Auth::user()) {

            return $next($request);
        }
        return response()->view('auth.login');

    }
}
