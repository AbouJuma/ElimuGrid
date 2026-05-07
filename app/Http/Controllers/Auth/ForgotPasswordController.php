<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Log;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        Log::info('******',[$request->all()]);
        $request->validate([
            'email' => 'required|email'
        ]);

        if ($request->school_code) {
            $school = School::where('code',$request->school_code)->first();
            if ($school) {
                DB::setDefaultConnection('school');
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');    
            }
        }

        try {
            $response = $this->broker()->sendResetLink(
                $request->only('email')
            );

            switch ($response) {
                case \Illuminate\Auth\Passwords\PasswordBroker::RESET_LINK_SENT:
                    Log::info('Password reset link sent successfully to: ' . $request->email);
                    return back()->with('status', trans($response));
                case \Illuminate\Auth\Passwords\PasswordBroker::INVALID_USER:
                    Log::warning('Password reset requested for invalid user: ' . $request->email);
                    return back()->withErrors(['email' => trans($response)]);
                default:
                    Log::error('Password reset failed with response: ' . $response);
                    return back()->withErrors(['email' => trans($response)]);
            }
        } catch (\Exception $e) {
            // Handle SMTP errors with detailed logging
            Log::error('Password reset email failed: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            return back()->withErrors(['email' => 'Email sending failed: ' . $e->getMessage()]);
        }
    }
}
