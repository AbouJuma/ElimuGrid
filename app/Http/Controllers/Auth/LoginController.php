<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Providers\RouteServiceProvider;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SharedHostingTenantService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\Role;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
    private CachingService $cache;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CachingService $cachingService)
    {
        $this->cache = $cachingService;
        $this->middleware('guest')->except('logout');
        // $this->middleware('2fa')->except('logout');
    }

    public function username()
    {
        $loginValue = request('email');
        $this->username = filter_var($loginValue ,FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        request()->merge([$this->username => $loginValue]);
        return $this->username == 'mobile' ? 'mobile' : 'email';
    }

    public function login(Request $request)
    {
        // Validate the login request
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'code' => 'nullable|string',
        ]);

        $loginField = $this->username();

        // maintainence mode is roles not allowes to access the site [ school admin, teacher ] only super admin allowed
        $data = DB::connection('mysql')->table('system_settings')->get();
        foreach ($data as $row) {
            if ($row->name == 'web_maintenance') {
                if ($row->data == "1") {
                    if ($request->code != null) {
                        return \Response::view('errors.503', [], 503);
                    }
                }
            }
        }

        if ($request->code ) {
            // Retrieve the school's database connection info
            $school = DB::table('schools')->where('code', $request->code)->first();

            if (!$school) {
                return back()->withErrors(['code' => 'Invalid school identifier.']);
            }

            // Check if school is active
            if ($school->status != 1) {
                return back()->withErrors(['code' => 'School is deactivated. Please contact administrator.']);
            }

            // Set the dynamic database connection (shared DB + prefix, or legacy separate schema)
            SharedHostingTenantService::switchToTenant($school->id);

            \Log::info('Switched to database: ' . DB::connection('school')->getDatabaseName() . ' with prefix: ' . DB::connection('school')->getTablePrefix());
            
            // Find user explicitly using school connection via Eloquent so we can log them in
            $user = \App\Models\User::on('school')->where('email', $request->email)->first();
            
            if (!$user) {
                \Log::error('User not found in tenant database. Email: ' . $request->email);
                return back()->withErrors(['email' => 'Invalid credentials. If you are a school staff or student, please ensure you have provided the correct School Code.']);
            }
            
            // Verify password
            if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                \Log::error('Invalid password for user. Email: ' . $request->email);
                return back()->withErrors(['email' => 'Invalid credentials. Use your mobile number as the password if it was not changed.']);
            }
            
            // Set up custom authentication session
            session([
                'auth_user_id' => $user->id,
                'auth_user_email' => $user->email,
                'auth_user_name' => $user->first_name . ' ' . $user->last_name,
                'auth_school_id' => $school->id,
                'auth_school_code' => $school->code,
                'auth_school_database' => $school->database_name,
                'auth_logged_in' => true,
                'auth_login_time' => now()
            ]);
            
            // Log the user into Laravel's authentication system so permissions work
            Auth::guard('web')->login($user);
            
            \Log::info('User authenticated successfully via custom session.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'school_id' => $school->id,
            ]);

            // Skip two-factor authentication for now since User model has database connection issues
            // TODO: Implement custom 2FA system that doesn't rely on User model
            
            // Redirect to dashboard
            return redirect()->intended('/dashboard');
        } else {
            // Attempt login on the main connection
            DB::setDefaultConnection('mysql');
            Session::forget('school_database_name');
            Session::flush();
            Session::put('school_database_name', null);
            if (Auth::guard('web')->attempt([
                $loginField => $request->email,
                'password' => $request->password,
            ])) {

                if (Auth::user()->school) {
                    Auth::logout();
                    $request->session()->flush();
                    $request->session()->regenerate();
                    session()->forget('school_database_name');
                    Session::forget('school_database_name');
                    return back()->withErrors(['email' => 'Invalid credentials. If you are a school staff or student, please ensure you have provided the correct School Code and are using your mobile number as the password if it was not changed.']);
                }

                $data = DB::table('users')->where('email',$request->email)->first();

                if ($data) {
                    if (( $data->two_factor_secret == null || $data->two_factor_expires_at == null ) && $data->two_factor_enabled == 1 && $request->email != 'demo@school.com' && !config('app.demo_mode')) {
                       
                        $twoFACode = $this->generate2FACode();
                        $settings = $this->cache->getSystemSettings();
                        $user = Auth::user();

                        DB::table('users')->where('email',$user->email)->update(['two_factor_secret' => $twoFACode, 'updated_at' => Carbon::now()]);

                        $adminData = DB::table('users')->where('email',$user->email)->first();
                        // dd('done',$adminData);
                        $this->send2FAEmail($adminData, $user, $settings, $twoFACode);

                        return redirect()->route('auth.2fa');
                    } else {
                        return redirect()->intended('/dashboard');
                    }
                }

                session(['db_connection_name' => 'mysql']);
                return redirect()->intended('/home');
            } else {
                \Log::error('Login attempt failed in main database. Email: ' . $request->email);
            }
        }

        // Login failed, redirect back with an error message
        return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    private function generate2FACode($length = 6)
    {
        // Define the characters to be used in the code
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';
        
        // Loop through and generate each character
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }


    public function send2FAEmail($schools, $user, $settings, $twoFACode)
    {

        try {
            $schools_name = $schools->first_name ." ". $schools->last_name;
            $emailBody = $this->replacePlaceholders($schools_name, $user, $settings, $twoFACode, $twoFACode);

            // Prepare the email data
            $data = [
                'subject' => '2FA Code for ' . $schools_name,
                'email' => $user['email'],
                'email_body' => $emailBody,
                'verification_code' => $twoFACode,
            ];

            // Send the email with the 2FA code
            Mail::send('schools.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });

            // Log the email sent for debugging purposes
            \Log::info('2FA code sent to: ' . $data['email']);
        } catch (\Throwable $th) {
            if (Str::contains($th->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                ResponseService::warningResponse("Message send successfully. But Email not sent.");
            } else {
                ResponseService::errorResponse(trans('error_occured'));
            }
        }
    }

    private function replacePlaceholders($school_name, $user, $settings, $school_code, $twoFACode)
    {
        $templateContent = $settings['email_template_two_factor_authentication_code'] ?? '';

        $systemSettings = $this->cache->getSystemSettings();

        $placeholders = [
            '{school_admin_name}' => $user->full_name,
            '{school_name}' => $school_name,
        
            '{super_admin_name}' => $settings['super_admin_name'] ?? 'Super Admin',
            '{support_email}' => $settings['mail_send_from'] ?? 'example@gmail.com',
            '{support_contact}' => $systemSettings['mobile'] ?? '9876543210',
            '{system_name}' => $settings['system_name'] ?? 'eSchool Saas',
            '{expiration_time}' => '5',
            '{url}' => url('/'),
        
            '{verification_code}' => $twoFACode,
        ];
        
        // Replace the placeholders in the template content
        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }
    
}
