<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SecurityMetadataViewer extends Component
{
    public function render()
    {
        $user = Auth::user();
        $metadata = $user->security_metadata ?? [];

        return view('livewire.profile.security-metadata-viewer', [
            'metadata' => $metadata,
            'lastLoginAt' => $user->last_login_at ?? 'Never',
        ]);
    }
}
