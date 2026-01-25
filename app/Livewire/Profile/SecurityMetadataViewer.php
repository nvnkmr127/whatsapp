<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

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
