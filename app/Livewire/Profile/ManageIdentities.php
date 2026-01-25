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

        if (!$identity->isSafeToUnlink()) {
            $this->confirmingIdentityUnlink = false;
            $this->dispatch('notify', [
                'message' => 'You cannot unlink your last remaining login method.',
                'style' => 'danger'
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
            'style' => 'success'
        ]);
    }

    public function render()
    {
        return view('livewire.profile.manage-identities', [
            'identities' => Auth::user()->identities()->latest()->get()
        ]);
    }
}
