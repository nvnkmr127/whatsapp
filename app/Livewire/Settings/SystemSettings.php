<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('System Settings')]
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
        'IN' => ['label' => 'India', 'country_code' => '+91', 'timezone' => 'Asia/Kolkata', 'currency' => 'INR', 'lang' => 'hi', 'policy' => 'India requires DLT/TRAI registration for templates. Category mixing is strictly prohibited.'],
        'AE' => ['label' => 'United Arab Emirates', 'country_code' => '+971', 'timezone' => 'Asia/Dubai', 'currency' => 'AED', 'lang' => 'ar', 'policy' => 'UAE requires official business registration and verified Meta Business Manager.'],
        'AU' => ['label' => 'Australia', 'country_code' => '+61', 'timezone' => 'Australia/Sydney', 'currency' => 'AUD', 'lang' => 'en', 'policy' => 'Australia Spam Act (2003) requires explicit opt-in and clear opt-out mechanisms.'],
        'IQ' => ['label' => 'Iraq', 'country_code' => '+964', 'timezone' => 'Asia/Baghdad', 'currency' => 'IQD', 'lang' => 'ar', 'policy' => 'Standard Meta commercial policies apply. Multilingual templates (Arabic/Kurdish) recommended.'],
        'US' => ['label' => 'United States', 'country_code' => '+1', 'timezone' => 'America/New_York', 'currency' => 'USD', 'lang' => 'en', 'policy' => 'USA requires strict adherence to TCPA/CTIA. STOP/UNSUBSCRIBE keywords are mandatory.'],
        'UK' => ['label' => 'United Kingdom', 'country_code' => '+44', 'timezone' => 'Europe/London', 'currency' => 'GBP', 'lang' => 'en', 'policy' => 'UK requires GDPR compliance. Marketing messages must have a clear opt-out method.'],
        'DE' => ['label' => 'European Union (DE)', 'country_code' => '+49', 'timezone' => 'Europe/Berlin', 'currency' => 'EUR', 'lang' => 'de', 'policy' => 'Strict GDPR compliance. Double opt-in is highly recommended for marketing.'],
        'SA' => ['label' => 'Saudi Arabia', 'country_code' => '+966', 'timezone' => 'Asia/Riyadh', 'currency' => 'SAR', 'lang' => 'ar', 'policy' => 'CITC regulations apply. Content should be culturally sensitive and in Arabic.'],
        'SG' => ['label' => 'Singapore', 'country_code' => '+65', 'timezone' => 'Asia/Singapore', 'currency' => 'SGD', 'lang' => 'en', 'policy' => 'PDPA compliance and DNC registry checks are mandatory for marketing.'],
        'NG' => ['label' => 'Nigeria', 'country_code' => '+234', 'timezone' => 'Africa/Lagos', 'currency' => 'NGN', 'lang' => 'en', 'policy' => 'NCC guidelines apply. Ensure compliance with local messaging regulations.'],
        'ES' => ['label' => 'Spain', 'country_code' => '+34', 'timezone' => 'Europe/Madrid', 'currency' => 'EUR', 'lang' => 'es', 'policy' => 'GDPR compliance. Marketing messages require explicit consent and easy opt-out.'],
    ];

    // Enhanced Settings
    public $primaryColor = '#4F46E5'; // Default Indigo

    public $currencySymbol = '$';

    public $dateFormat = 'Y-m-d';

    public $paginationLimit = 20;

    public $supportEmail;

    public $maintenanceMode = false;

    // System WhatsApp Configuration
    public $systemWabaId;

    public $systemPhoneNumberId;

    public $systemAccessToken;

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

    protected function rules()
    {
        return [
            'teamName' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'in:'.implode(',', array_keys($this->timezones))],
            'logo' => ['nullable', 'image', 'max:2048'], // 2MB max
            // Use array syntax to prevent pipe delimiter collision in regex
            'primaryColor' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'currencySymbol' => ['required', 'string', 'max:10'],
            'dateFormat' => ['required', 'string', 'in:Y-m-d,d/m/Y,m/d/Y,d-m-Y'],
            'paginationLimit' => ['required', 'integer', 'min:5', 'max:100'],
            'supportEmail' => ['nullable', 'email'],
            'maintenanceMode' => ['boolean'],
            'selectedCountry' => ['nullable', 'string', 'in:IN,AE,AU,IQ,US,UK,DE,SA,SG,NG,ES'],
            'language' => ['required', 'string', 'max:5'],
        ];
    }

    public function updatedSelectedCountry($value)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        if (isset($this->countries[$value])) {
            $country = $this->countries[$value];
            $this->timezone = $country['timezone'];
            $this->currencySymbol = $country['currency'];
            $this->language = $country['lang'];
            $this->metaPolicyInfo = $country['policy'];

            // Auto-save timezone and related settings immediately
            $team = Auth::user()->currentTeam;
            if ($team) {
                $team->timezone = $this->timezone;
                $team->save();
            }

            // Update global settings
            set_setting('selected_country', $value, 'system');
            set_setting('currency_symbol', $this->currencySymbol, 'system');
            set_setting('primary_language', $this->language, 'system');
            set_setting('default_country_code', $country['country_code'], 'system');

            session()->flash('message', 'Country settings updated! Timezone changed to '.$country['timezone'].', Country code set to '.$country['country_code']);
            audit('settings.country_changed', "Country changed to {$country['label']} by ".Auth::user()->name);
            $this->dispatch('saved');
        }
    }

    public function mount()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        $team = Auth::user()->currentTeam;

        if (! $team) {
            // Handle case where team is null (e.g., redirect or set defaults)
            // For now, we'll initialize empty values to prevent crashes
            $this->teamName = '';
            $this->timezone = 'UTC';
            $this->currentLogoPath = null;
        } else {
            $this->teamName = $team->name;
            $this->timezone = $team->timezone ?? 'UTC';
            $this->currentLogoPath = $team->logo_path;
        }

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

        // Load System WhatsApp Settings
        $this->systemWabaId = $settings['system_waba_id'] ?? '';
        $this->systemPhoneNumberId = $settings['system_phone_number_id'] ?? '';
        $this->systemAccessToken = $settings['system_access_token'] ?? '';

        if ($this->selectedCountry && isset($this->countries[$this->selectedCountry])) {
            $this->metaPolicyInfo = $this->countries[$this->selectedCountry]['policy'];
        }
    }

    public function save()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        $this->validate([
            'teamName' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'in:'.implode(',', array_keys($this->timezones))],
            'logo' => ['nullable', 'image', 'max:2048'],
            'primaryColor' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'currencySymbol' => ['required', 'string', 'max:10'],
            'dateFormat' => ['required', 'string', 'in:Y-m-d,d/m/Y,m/d/Y,d-m-Y'],
            'paginationLimit' => ['required', 'integer', 'min:5', 'max:100'],
            'supportEmail' => ['nullable', 'email'],
            'maintenanceMode' => ['boolean'],
            'selectedCountry' => ['nullable', 'string', 'in:IN,AE,AU,IQ,US,UK,DE,SA,SG,NG,ES'],
            'language' => ['required', 'string', 'max:5'],
            'systemWabaId' => ['nullable', 'string'],
            'systemPhoneNumberId' => ['nullable', 'string'],
            'systemAccessToken' => ['nullable', 'string'],
        ]);

        $team = Auth::user()->currentTeam;

        if ($team) {
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

            $this->currentLogoPath = $team->logo_path;
        }

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
            'system_waba_id' => $this->systemWabaId,
            'system_phone_number_id' => $this->systemPhoneNumberId,
            'system_access_token' => $this->systemAccessToken,
        ];

        foreach ($settings as $key => $value) {
            set_setting($key, $value, 'system');
        }

        if ($team) {
            $this->currentLogoPath = $team->logo_path;
        }

        session()->flash('message', 'System settings updated successfully.');
        audit('settings.updated', 'System settings updated by '.Auth::user()->name);
        $this->dispatch('saved');

        // Full page redirect to reflect branding changes (logo, primary color, team name) in the layout
        return redirect()->route('settings.system');
    }

    public function removeLogo()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        $team = Auth::user()->currentTeam;

        if ($team && $team->logo_path) {
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
