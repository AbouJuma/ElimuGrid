<?php

namespace dacoto\LaravelWizardInstaller\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\SharedHostingTenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ToInstallMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return RedirectResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->alreadyInstalled() && explode('/', $request->route() ? $request->route()->uri() : '')[0] !== 'install') {
            return redirect()->route('LaravelWizardInstaller::install.index');
        }
        try {
            $school_database_name = Session::get('school_database_name');
            if ($school_database_name) {
                SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school_database_name);
                DB::setDefaultConnection('school');
            }
        } catch (\Throwable $th) {

        }
        
        return $next($request);
    }

    /**
     * If application is already installed.
     *
     * @return bool
     */
    public function alreadyInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }
}
