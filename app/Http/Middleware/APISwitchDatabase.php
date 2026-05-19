<?php

namespace App\Http\Middleware;

use App\Models\School;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use App\Services\SharedHostingTenantService;
use Symfony\Component\HttpFoundation\Response;

class APISwitchDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
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

                $exclude_uri = array(
                    '/api/student/login',
                    '/api/parent/login',
                    '/api/teacher/login',
                    '/contact',
                    '/api/student/submit-online-exam-answers',
                );

                if (config('app.demo_mode') && !$request->isMethod('get') && Auth::user() && !in_array($request->getRequestUri(), $exclude_uri)) {
                    return response()->json(array(
                        'error'   => true,
                        'message' => "This is not allowed in the Demo Version.",
                        'code'    => 112
                    ));
                }

            } else {
                return response()->json(['message' => 'Invalid school code'], 400);
            }
        } else {
            return response()->json(['message' => 'Unauthenticated'], 400);
        }
        return $next($request);
    }
}
