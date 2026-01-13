<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        try {
            $request->user()->sendEmailVerificationNotification();

            return back()->with('status', t('verification_link_sent'));
        } catch (\Exception $e) {
            return back()->with('error', t('verification_error'));
        }

        return back()->with('status', t('verification_link_sent'));
    }
}
