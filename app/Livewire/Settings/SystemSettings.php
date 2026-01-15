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
    ];

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
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'system']
            );
        }

        $this->currentLogoPath = $team->logo_path;

        session()->flash('message', 'System settings updated successfully.');
        $this->dispatch('saved');
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
