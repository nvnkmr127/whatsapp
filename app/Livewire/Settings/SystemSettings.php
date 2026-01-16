<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

class SystemSettings extends Component
{
    use WithFileUploads;

    public $teamName;
    public $timezone;
    public $logo;
    public $currentLogoPath;

    // Smart Country Settings
    public $selectedCountry;
    public $language = 'en';
    public $metaPolicyInfo = '';

    public $countries = [
        'IN' => ['label' => 'India', 'timezone' => 'Asia/Kolkata', 'currency' => 'INR', 'lang' => 'hi', 'policy' => 'India requires DLT/TRAI registration for templates. Category mixing is strictly prohibited.'],
        'AE' => ['label' => 'United Arab Emirates', 'timezone' => 'Asia/Dubai', 'currency' => 'AED', 'lang' => 'ar', 'policy' => 'UAE requires official business registration and verified Meta Business Manager.'],
        'AU' => ['label' => 'Australia', 'timezone' => 'Australia/Sydney', 'currency' => 'AUD', 'lang' => 'en', 'policy' => 'Australia Spam Act (2003) requires explicit opt-in and clear opt-out mechanisms.'],
        'IQ' => ['label' => 'Iraq', 'timezone' => 'Asia/Baghdad', 'currency' => 'IQD', 'lang' => 'ar', 'policy' => 'Standard Meta commercial policies apply. Multilingual templates (Arabic/Kurdish) recommended.'],
        'US' => ['label' => 'United States', 'timezone' => 'America/New_York', 'currency' => 'USD', 'lang' => 'en', 'policy' => 'USA requires strict adherence to TCPA/CTIA. STOP/UNSUBSCRIBE keywords are mandatory.'],
    ];

    // Enhanced Settings
    public $primaryColor = '#4F46E5'; // Default Indigo
    public $currencySymbol = '$';
    public $dateFormat = 'Y-m-d';
    public $paginationLimit = 20;
    public $supportEmail;
    public $maintenanceMode = false;

    public $timezones = [
        'UTC' => 'UTC',
        'America/New_York' => 'Eastern Time (US & Canada)',
        'America/Chicago' => 'Central Time (US & Canada)',
        'America/Denver' => 'Mountain Time (US & Canada)',
        'America/Los_Angeles' => 'Pacific Time (US & Canada)',
        'America/Phoenix' => 'Arizona',
        'America/Anchorage' => 'Alaska',
        'Pacific/Honolulu' => 'Hawaii',
        'Europe/London' => 'London',
        'Europe/Paris' => 'Paris',
        'Europe/Berlin' => 'Berlin',
        'Europe/Rome' => 'Rome',
        'Europe/Madrid' => 'Madrid',
        'Asia/Dubai' => 'Dubai',
        'Asia/Kolkata' => 'India Standard Time',
        'Asia/Baghdad' => 'Baghdad',
        'Asia/Singapore' => 'Singapore',
        'Asia/Tokyo' => 'Tokyo',
        'Asia/Shanghai' => 'Beijing, Shanghai',
        'Asia/Hong_Kong' => 'Hong Kong',
        'Australia/Sydney' => 'Sydney',
        'Australia/Melbourne' => 'Melbourne',
        'Pacific/Auckland' => 'Auckland',
    ];

    protected $rules = [
        'teamName' => 'required|string|max:255',
        'timezone' => 'required|string',
        'logo' => 'nullable|image|max:2048', // 2MB max
        'primaryColor' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        'currencySymbol' => 'required|string|max:10',
        'dateFormat' => 'required|string|in:Y-m-d,d/m/Y,m/d/Y,d-m-Y',
        'paginationLimit' => 'required|integer|min:5|max:100',
        'supportEmail' => 'nullable|email',
        'maintenanceMode' => 'boolean',
        'selectedCountry' => 'nullable|string|in:IN,AE,AU,IQ,US',
        'language' => 'required|string|max:5',
    ];

    public function updatedSelectedCountry($value)
    {
        if (isset($this->countries[$value])) {
            $country = $this->countries[$value];
            $this->timezone = $country['timezone'];
            $this->currencySymbol = $country['currency'];
            $this->language = $country['lang'];
            $this->metaPolicyInfo = $country['policy'];
        }
    }

    public function mount()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        $team = Auth::user()->currentTeam;
        $this->teamName = $team->name;
        $this->timezone = $team->timezone ?? 'UTC';
        $this->currentLogoPath = $team->logo_path;

        // Load Global Settings
        $settings = \App\Models\Setting::all()->pluck('value', 'key');

        $this->primaryColor = $settings['brand_primary_color'] ?? '#4F46E5';
        $this->currencySymbol = $settings['currency_symbol'] ?? '$';
        $this->dateFormat = $settings['date_format'] ?? 'Y-m-d';
        $this->paginationLimit = $settings['pagination_limit'] ?? 20;
        $this->supportEmail = $settings['support_email'] ?? '';
        $this->maintenanceMode = filter_var($settings['maintenance_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->selectedCountry = $settings['selected_country'] ?? null;
        $this->language = $settings['primary_language'] ?? 'en';

        if ($this->selectedCountry && isset($this->countries[$this->selectedCountry])) {
            $this->metaPolicyInfo = $this->countries[$this->selectedCountry]['policy'];
        }
    }

    public function save()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        $this->validate();

        $team = Auth::user()->currentTeam;

        // Handle logo upload
        if ($this->logo) {
            if ($team->logo_path) {
                Storage::disk('public')->delete($team->logo_path);
            }
            $path = $this->logo->store('team-logos', 'public');
            $team->logo_path = $path;
        }

        // Update team settings
        $team->name = $this->teamName;
        $team->timezone = $this->timezone;
        $team->save();

        // Save Global Settings
        $settings = [
            'brand_primary_color' => $this->primaryColor,
            'currency_symbol' => $this->currencySymbol,
            'date_format' => $this->dateFormat,
            'pagination_limit' => $this->paginationLimit,
            'support_email' => $this->supportEmail,
            'maintenance_mode' => $this->maintenanceMode,
            'selected_country' => $this->selectedCountry,
            'primary_language' => $this->language,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'system']
            );
        }

        $this->currentLogoPath = $team->logo_path;

        session()->flash('message', 'System settings updated successfully.');
        audit('settings.updated', "System settings updated by " . Auth::user()->name);
        $this->dispatch('saved');

        // Full page redirect to reflect branding changes (logo, primary color, team name) in the layout
        return redirect()->route('settings.system');
    }

    public function removeLogo()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        $team = Auth::user()->currentTeam;

        if ($team->logo_path) {
            Storage::disk('public')->delete($team->logo_path);
            $team->logo_path = null;
            $team->save();

            $this->currentLogoPath = null;
            session()->flash('message', 'Logo removed successfully.');
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.settings.system-settings');
    }
}
