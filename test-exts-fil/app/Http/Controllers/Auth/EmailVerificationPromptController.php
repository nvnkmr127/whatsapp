<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if (! can_send_email('email-confirmation')) {
            $announcement = get_settings_by_group('announcement');

            return view('auth.login', compact('announcement'));
        }

        return $request->user()->hasVerifiedEmail()
        ? redirect()->intended(route('admin.dashboard', absolute : false))
        : view('auth.verify-email');
    }
}
