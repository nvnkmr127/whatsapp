<?php

namespace App\Livewire\Developer;

use Livewire\Component;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\Validator;

class ApiTokenManager extends Component
{
    public $name = '';
    public $permissions = [];
    public $ipWhitelist = '';
    public $expiresAt = null;
    public $showTokenModal = false;
    public $plainTextToken = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'permissions' => 'array',
        'ipWhitelist' => 'nullable|string',
        'expiresAt' => 'nullable|date|after:today',
    ];

    public function mount()
    {
        $this->permissions = Jetstream::$defaultPermissions;
    }

    public function createToken()
    {
        $this->validate();

        $expiry = $this->expiresAt ? \Carbon\Carbon::parse($this->expiresAt) : null;
        $token = auth()->user()->createToken($this->name, $this->permissions, $expiry);

        // Update the extra metadata (IP Whitelist)
        $token->accessToken->update([
            'ip_whitelist' => $this->ipWhitelist ? json_encode(array_map('trim', explode(',', $this->ipWhitelist))) : null,
        ]);

        $this->plainTextToken = $token->plainTextToken;
        $this->showTokenModal = true;
        
        // Reset form
        $this->name = '';
        $this->permissions = Jetstream::$defaultPermissions;
        $this->ipWhitelist = '';
        $this->expiresAt = null;

        $this->dispatch('notify', 'API Token created successfully.');
    }

    public function deleteToken($tokenId)
    {
        auth()->user()->tokens()->where('id', $tokenId)->delete();
        $this->dispatch('notify', 'API Token deleted successfully.');
    }

    #[\Livewire\Attributes\Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.developer.api-token-manager', [
            'tokens' => auth()->user()->tokens()->latest()->limit(50)->get(),
            'availablePermissions' => Jetstream::$permissions,
            'defaultPermissions' => Jetstream::$defaultPermissions,
        ]);
    }
}
