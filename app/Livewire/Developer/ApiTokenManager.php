<?php

namespace App\Livewire\Developer;

use Livewire\Component;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\Validator;

class ApiTokenManager extends Component
{
    public $name = '';
    public $permissions = [];
    public $showTokenModal = false;
    public $plainTextToken = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'permissions' => 'array',
    ];

    public function mount()
    {
        $this->permissions = Jetstream::$defaultPermissions;
    }

    public function createToken()
    {
        $this->validate();

        $token = auth()->user()->createToken($this->name, $this->permissions);

        $this->plainTextToken = $token->plainTextToken;
        $this->showTokenModal = true;
        $this->name = '';
        $this->permissions = Jetstream::$defaultPermissions;

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
            'tokens' => auth()->user()->tokens()->latest()->get(),
            'availablePermissions' => Jetstream::$permissions,
            'defaultPermissions' => Jetstream::$defaultPermissions,
        ]);
    }
}
