<?php

namespace App\Livewire\Crm;

use Livewire\Component;

class CrmHub extends Component
{
    public $activeTab = 'dashboard';

    protected $queryString = [
        'activeTab' => ['except' => 'dashboard', 'as' => 'view'],
    ];

    public function setTab($tab)
    {
        if (in_array($tab, ['dashboard', 'contacts', 'deals', 'companies', 'onboarding', 'affiliates'])) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.crm.crm-hub');
    }
}
