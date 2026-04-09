<?php

namespace App\Livewire\Crm;

use App\Models\Affiliate;
use Livewire\Component;
use Livewire\WithPagination;

class AffiliateManager extends Component
{
    use WithPagination;

    public $showCreateModal = false;

    public $newAffiliate = [
        'user_id' => '',
        'code' => '',
        'commission_rate' => 10,
    ];

    public function create()
    {
        $this->newAffiliate = ['user_id' => '', 'code' => '', 'commission_rate' => 10];
        $this->showCreateModal = true;
    }

    public function save()
    {
        $this->validate([
            'newAffiliate.user_id' => 'required|exists:users,id',
            'newAffiliate.code' => 'required|unique:affiliates,code',
            'newAffiliate.commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        Affiliate::create($this->newAffiliate);
        $this->showCreateModal = false;
        $this->dispatch('notify', type: 'success', message: 'Affiliate created successfully.');
    }

    public function render()
    {
        $affiliates = Affiliate::with('user')->withCount('referrals')->paginate(10);
        $users = \App\Models\User::orderBy('name')->limit(50)->get(); // Should be searchable via select2 ideally

        return view('livewire.crm.affiliate-manager', [
            'affiliates' => $affiliates,
            'users' => $users,
        ]);
    }
}
