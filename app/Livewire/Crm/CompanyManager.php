<?php

namespace App\Livewire\Crm;

use App\Models\Company;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class CompanyManager extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $companyId;

    // Form fields
    public $name;
    public $domain;
    public $industry;
    public $size;
    public $website;
    public $phone;
    public $city;
    public $country;
    public $description;

    protected $rules = [
        'name' => 'required|string|max:255',
        'domain' => 'nullable|string|max:255',
        'industry' => 'nullable|string|max:255',
        'size' => 'nullable|string|max:50',
        'website' => 'nullable|url|max:255',
        'phone' => 'nullable|string|max:20',
        'city' => 'nullable|string|max:100',
        'country' => 'nullable|string|max:100',
        'description' => 'nullable|string',
    ];

    public function render()
    {
        $query = Company::where('team_id', Auth::user()->currentTeam->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('domain', 'like', '%' . $this->search . '%')
                    ->orWhere('industry', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.crm.company-manager', [
            'companies' => $query->latest()->paginate(12),
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $company = Company::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->companyId = $id;
        $this->name = $company->name;
        $this->domain = $company->domain;
        $this->industry = $company->industry;
        $this->size = $company->size;
        $this->website = $company->website;
        $this->phone = $company->phone;
        $this->city = $company->city;
        $this->country = $company->country;
        $this->description = $company->description;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate();

        Company::updateOrCreate(
            ['id' => $this->companyId, 'team_id' => Auth::user()->currentTeam->id],
            [
                'name' => $this->name,
                'domain' => $this->domain,
                'industry' => $this->industry,
                'size' => $this->size,
                'website' => $this->website,
                'phone' => $this->phone,
                'city' => $this->city,
                'country' => $this->country,
                'description' => $this->description,
            ]
        );

        session()->flash('message', $this->companyId ? 'Company updated.' : 'Company created.');
        $this->isModalOpen = false;
        $this->resetInput();
    }

    public function confirmDelete($id)
    {
        $this->companyId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        Company::where('team_id', Auth::user()->currentTeam->id)->where('id', $this->companyId)->delete();
        $this->isDeleteModalOpen = false;
        $this->resetInput();
        session()->flash('message', 'Company deleted.');
    }

    private function resetInput()
    {
        $this->companyId = null;
        $this->name = '';
        $this->domain = '';
        $this->industry = '';
        $this->size = '';
        $this->website = '';
        $this->phone = '';
        $this->city = '';
        $this->country = '';
        $this->description = '';
    }
}
