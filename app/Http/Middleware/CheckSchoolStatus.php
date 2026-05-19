<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Services\SharedHostingTenantService;
use Laravel\Sanctum\PersonalAccessToken;

class CheckSchoolStatus {

    public function handle(Request $request, Closure $next) {

        $url = $request->getRequestUri();
        // For api routes
        if (strpos($url, 'api') !== false) {
            $schoolCode = $request->header('school-code');
            if ($schoolCode) {
                $school = School::on('mysql')->where('code',$schoolCode)->first();

                if ($school) {
                    SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school->database_name);
                    DB::setDefaultConnection('school');
                    $token = $request->bearerToken();
                    $user = PersonalAccessToken::findToken($token);
                    
                    if ($user) {
                        Auth::loginUsingId($user->tokenable_id);
                    } else {
                        return response()->json(['message' => 'Unauthenticated.']);    
                    }
    
                } else {
                    return response()->json(['message' => 'Invalid school code'], 400);
                }
            }
        } else {
            // For web routes
            $school_database_name = Session::get('school_database_name');
            if ($school_database_name && SharedHostingTenantService::usesPrefixedTenantTables($school_database_name)) {
                $schoolId = SharedHostingTenantService::schoolIdFromPrefixedDatabaseName($school_database_name);
                if ($schoolId) {
                    SharedHostingTenantService::switchToTenant($schoolId);
                }
            } elseif ($school_database_name) {
                SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school_database_name);
                DB::setDefaultConnection('school');
            } else {
                SharedHostingTenantService::switchToMain();
            }
        }
        // ==========================================================
        // $school_database_name = Session::get('school_database_name');
        // if ($school_database_name) {
        //     DB::setDefaultConnection('school');
        //     Config::set('database.connections.school.database', $school_database_name);
        //     DB::purge('school');
        //     DB::connection('school')->reconnect();
        //     DB::setDefaultConnection('school');
        // } else {
        //     DB::purge('school');
        //     DB::connection('mysql')->reconnect();
        //     DB::setDefaultConnection('mysql');
        // }

        // =========================================================
        $user = Auth::user();
        if (isset(Auth::user()->school)) {
            // Check Student, Teacher status for app
            $requestURL = $request->getRequestUri();
            if (stripos($requestURL, 'api') !== false) { // Api routes
                if (Auth::user()->hasRole('Student') || Auth::user()->hasRole('Teacher')) {
                    // Check user status
                    if ($user->status == 0) {
                        $user = $request->user();
                        $user->fcm_id = '';
                        $user->save();
                        $user->currentAccessToken()->delete();
                        return response()->json(['error' => true, 'message' => trans('your_account_has_been_deactivated_please_contact_admin')]);
                    }
                    
                    // Check school status from database name
                    if ($school) {
                        $schoolData = DB::connection('mysql')->table('schools')->where('id', str_replace(['s', '_'], '', $school->database_name))->first();
                        if ($schoolData && $schoolData->status == 0) {
                            $user = $request->user();
                            $user->fcm_id = '';
                            $user->save();
                            $user->currentAccessToken()->delete();
                            return response()->json(['error' => true, 'message' => trans('your_account_has_been_deactivated_please_contact_admin')]);
                        }
                    }
                }
            } else {
                if ($user->hasRole('Student') || $user->hasRole('Guardian')) {
                    Auth::logout();
                    $request->session()->flush();
                    $request->session()->regenerate();
                    return redirect()->route('login')->withErrors(trans('no_permission_message'));
                }

                // Check school status from session or database
                $school_database_name = Session::get('school_database_name');
                if ($school_database_name) {
                    // Extract school ID from database name (s56_ -> 56)
                    $schoolId = str_replace(['s', '_'], '', $school_database_name);
                    if (is_numeric($schoolId)) {
                        // Check school status from main database
                        $school = DB::connection('mysql')->table('schools')->where('id', $schoolId)->first();
                        if ($school && $school->status == 0) {
                            Auth::logout();
                            $request->session()->flush();
                            $request->session()->regenerate();
                            return redirect()->route('login')->withErrors(trans('your_account_has_been_deactivated_please_contact_admin'));
                        }
                    }
                }
                
            }
        }
        return $next($request);
    }
}
