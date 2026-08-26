<?php

namespace App\Livewire\Profile;

use App\Models\UserIdentity;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ManageIdentities extends Component
{
    public $confirmingIdentityUnlink = false;

    public $identityIdBeingUnlinked = null;

    public $confirmingLinkPhone = false;
    public $linkPhoneIdentifier = '';
    public $linkPhoneStep = 'request'; // request or verify
    public $linkPhoneCode = '';
    public $linkPhoneError = '';
    public $linkPhoneMessage = '';
    public $resendCountdown = 0;

    /**
     * Confirm that the user wants to unlink an identity.
     *
     * @param  int  $identityId
     * @return void
     */
    public function confirmIdentityUnlink($identityId)
    {
        $this->identityIdBeingUnlinked = $identityId;
        $this->confirmingIdentityUnlink = true;
    }

    /**
     * Unlink the chosen identity.
     *
     * @return void
     */
    public function unlinkIdentity()
    {
        $identity = UserIdentity::findOrFail($this->identityIdBeingUnlinked);

        if ($identity->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $identity->isSafeToUnlink()) {
            $this->confirmingIdentityUnlink = false;
            $this->dispatch('notify', [
                'message' => 'You cannot unlink your last remaining login method.',
                'style' => 'danger',
            ]);

            return;
        }

        $provider = $identity->provider;

        AuditService::log('Identity.Unlink', Auth::id(), Auth::user()->email, 'security', [
            'provider' => $provider,
            'provider_id' => $identity->provider_id,
        ]);

        $identity->delete();

        $this->confirmingIdentityUnlink = false;
        $this->identityIdBeingUnlinked = null;

        $this->dispatch('notify', [
            'message' => "Successfully unlinked your {$provider} account.",
            'style' => 'success',
        ]);
    }

    public function requestLinkPhoneOtp(\App\Services\OTPService $otpService)
    {
        $this->linkPhoneIdentifier = preg_replace('/[^0-9+]/', '', $this->linkPhoneIdentifier);
        if (! str_starts_with($this->linkPhoneIdentifier, '+')) {
            $this->linkPhoneIdentifier = '+'.$this->linkPhoneIdentifier;
        }

        $this->validate([
            'linkPhoneIdentifier' => 'required',
        ]);

        $this->linkPhoneError = '';
        $this->linkPhoneMessage = '';

        // Check if identity is already linked to another user
        $existingIdentity = UserIdentity::where('provider', 'phone_otp')
            ->where('provider_id', $this->linkPhoneIdentifier)
            ->where('user_id', '!=', Auth::id())
            ->first();

        if ($existingIdentity) {
            $this->linkPhoneError = 'This phone number is already linked to another account.';
            return;
        }

        $lastSent = session('otp_last_sent_at');
        if ($lastSent && now()->diffInSeconds($lastSent) < 60) {
            $remaining = 60 - now()->diffInSeconds($lastSent);
            $this->linkPhoneError = "Please wait {$remaining} seconds before requesting a new code.";
            $this->dispatch('start-timer', duration: $remaining);
            return;
        }

        try {
            $sent = $otpService->send($this->linkPhoneIdentifier, 'phone');

            if ($sent) {
                session(['otp_last_sent_at' => now()]);
                $this->linkPhoneStep = 'verify';
                $this->linkPhoneMessage = 'A 6-digit code has been sent to your WhatsApp.';
                $this->resendCountdown = 60;
                $this->dispatch('start-timer', duration: 60);
            } else {
                $this->linkPhoneError = 'Failed to send OTP. Please check your details and try again.';
            }
        } catch (\Exception $e) {
            $this->linkPhoneError = 'An unexpected error occurred. Please try again later.';
            \Illuminate\Support\Facades\Log::error('OTP Request Error: '.$e->getMessage());
        }
    }

    public function verifyLinkPhoneOtp(\App\Services\OTPService $otpService)
    {
        $this->validate(['linkPhoneCode' => 'required|string|size:6']);
        $this->linkPhoneError = '';

        if (! $otpService->verify($this->linkPhoneIdentifier, $this->linkPhoneCode, false)) {
            $this->linkPhoneError = 'Invalid or expired code.';
            return;
        }

        $user = Auth::user();

        UserIdentity::updateOrCreate(
            ['provider' => 'phone_otp', 'provider_id' => $this->linkPhoneIdentifier],
            ['user_id' => $user->id, 'last_login_at' => now()]
        );

        if (empty($user->phone)) {
            $user->forceFill(['phone' => $this->linkPhoneIdentifier])->save();
        }

        AuditService::log('Identity.Link', $user->id, $user->email, 'security', [
            'provider' => 'phone_otp',
            'provider_id' => $this->linkPhoneIdentifier,
        ]);

        $this->confirmingLinkPhone = false;
        $this->linkPhoneStep = 'request';
        $this->linkPhoneIdentifier = '';
        $this->linkPhoneCode = '';

        $this->dispatch('notify', [
            'message' => "Successfully linked your phone number.",
            'style' => 'success',
        ]);
    }

    public function render()
    {
        return view('livewire.profile.manage-identities', [
            'identities' => Auth::user()->identities()->latest()->limit(50)->get(),
        ]);
    }
}
