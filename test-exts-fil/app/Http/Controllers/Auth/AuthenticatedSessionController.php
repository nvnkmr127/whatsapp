<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        $announcement = get_settings_by_group('announcement');
        $user         = Auth::user();
        if (! $user) {
            return view('auth.login', compact('announcement'));
        }

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if (get_setting('re-captcha.isReCaptchaEnable')) {

            $request->validate([
                'g-recaptcha-response' => ['required'],
            ]);

            $recaptchaResponse = $request->input('g-recaptcha-response');
            $secretKey         = get_setting('re-captcha.secret_key');

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secretKey,
                'response' => $recaptchaResponse,
                'remoteip' => $request->ip(),
            ]);

            $recaptchaResult = $response->json();

            if (! $recaptchaResult['success'] || $recaptchaResult['score'] < 0.5) {
                throw ValidationException::withMessages([
                    'g-recaptcha-response' => [t('email_recaptcha_failed')],
                ]);
            }
        }

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user && $user->active == 0) {
            session()->flash('error', t('user_deactivated_message_in_login'));

            return back();
        }

        $request->authenticate();

        $remember = $request->has('remember');

        if (Auth::attempt($request->only('email', 'password'), $remember)) {
            $request->session()->regenerate();

            $user   = Auth::user();
            $locale = Session::get('locale', config('app.locale'));
            Cache::forget("translations.{$locale}");
            $default_language = (! is_null($user->default_language)) ? $user->default_language : get_setting('general.active_language');
            Session::put('locale', $default_language);
            App::setLocale($default_language);
            $user->last_login = now();
            $user->save();

            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return back()->withErrors(['email' => t('provided_credential_not_match')]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}