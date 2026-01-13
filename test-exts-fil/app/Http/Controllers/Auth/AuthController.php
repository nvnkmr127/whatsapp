<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationMail;
use App\Models\User;
use App\Traits\SendMailTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    use SendMailTrait;

    public function forgot(Request $request)
    {
        if (! can_send_email('password-reset')) {
            return redirect()->back();
        }

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return redirect()->back()->withErrors(['email' => t('user_not_found')]);
        }

        if (! $user->email_verified_at) {
            return redirect()->back()->withErrors(['email' => t('user_not_verified')]);
        }

        $resetUrl = $this->generatePasswordResetUrl($user);

        if (isSmtpValid()) {
            $isSent = $this->sendMail($user->email, new VerificationMail("Hello {$user->name}", $resetUrl, t('reset_password_rp'), 'password-reset', $user->id));

            return $isSent['status']
            ? redirect()->back()->with('status', t('email_sent_successfull_with_emoji'))
            : redirect()->back()->with('error', t('email_not_sent_try_again'));
        }

        return redirect()->back()->withErrors(['email' => t('email_service_not_configured')]);
    }

    public function verified()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->back()->with('error', t('user_not_found'));
        }

        if ($user->email_verified_at) {
            return to_route('admin.dashboard');
        }

        $varifiedUrl = $this->generateEmailVerificationUrl($user);

        if (isSmtpValid()) {

            $isSent = $this->sendMail($user->email, new VerificationMail("Hello {$user->name}", $varifiedUrl, t('Click To Verify Email'), 'email-confirmation', $user->id));

            return $isSent['status']
            ? redirect()->back()->with('status', t('email_sent_successfull_with_emoji'))
            : redirect()->back()->with('error', t('email_not_sent_try_again'));
        }

        return redirect()->back()->with('error', t('email_service_not_configured'));
    }

    public function generatePasswordResetUrl($user)
    {
        if (! can_send_email('password-reset')) {
            return redirect()->back();
        }

        $token = Password::createToken($user);

        return URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes(Config::get('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60)),
            ['token' => $token, 'email' => $user->email]
        );
    }

    public function generateEmailVerificationUrl($user)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );
    }
}
