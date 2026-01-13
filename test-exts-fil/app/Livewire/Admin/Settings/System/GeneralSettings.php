<?php

namespace App\Livewire\Admin\Settings\System;

use App\Rules\PurifiedInput;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithFileUploads;

class GeneralSettings extends Component
{
    use WithFileUploads;

    public ?string $site_name = '';

    public ?string $site_description = '';

    public ?string $timezone = '';

    public ?string $date_format = '';

    public ?string $time_format = '';

    public ?string $active_language = '';

    public $site_logo;

    public $site_dark_logo;

    public $favicon;

    public $cover_page_image;

    public ?array $timezone_list = [];

    public $id;

    private array $keys = [
        'site_name'        => '',
        'site_description' => '',
        'timezone'         => '',
        'date_format'      => '',
        'time_format'      => '',
        'active_language'  => '',
    ];

    public array $date_formats = [
        'Y-m-d' => 'Y-m-d',
        'd/m/Y' => 'd/m/Y',
        'm/d/Y' => 'm/d/Y',
        'd.m.Y' => 'd.m.Y',
        'd-m-Y' => 'd-m-Y',
        'm-d-Y' => 'm-d-Y',
        'm.d.Y' => 'm.d.Y',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    protected function rules()
    {
        return [
            'site_name'        => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
            'site_description' => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
            'timezone'         => 'nullable|string|timezone',
            'date_format'      => 'nullable|string',
            'active_language'  => 'nullable|string',
            'time_format'      => 'nullable|string',
            'site_logo'        => 'nullable|image|mimes:png,jpg,jpeg',
            'site_dark_logo'   => 'nullable|image|mimes:png,jpg,jpeg',
            'favicon'          => 'nullable|image|mimes:png,jpg,jpeg',
            'cover_page_image' => 'nullable|image|mimes:png,jpg,jpeg|dimensions:width729,height=152',
        ];
    }

    public function mount()
    {
        if (! checkPermission('system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }

        $this->id            = $this->getId();
        $this->timezone_list = timezone_identifiers_list();
        $this->id            = $this->getId();

        $general_settings = get_settings_by_group('general');

        $this->site_name        = $general_settings->site_name        ?? '';
        $this->site_description = $general_settings->site_description ?? '';
        $this->timezone         = $general_settings->timezone         ?? '';
        $this->date_format      = $general_settings->date_format      ?? '';
        $this->time_format      = $general_settings->time_format      ?? '';
        $this->active_language  = $general_settings->active_language  ?? '';
    }

    public function save()
    {
        if (checkPermission('system_settings.edit')) {
            $this->validate();

            $originalSettings = get_settings_by_group('general');
            Cache::forget("translations.{$originalSettings->active_language}");

            $newSettings = [
                'general.site_name'        => $this->site_name,
                'general.site_description' => $this->site_description,
                'general.timezone'         => $this->timezone,
                'general.date_format'      => $this->date_format,
                'general.time_format'      => $this->time_format,
                'general.active_language'  => $this->active_language,
            ];

            Session::put('locale', $this->active_language);
            App::setLocale($this->active_language);

            // Compare and filter only modified settings
            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                $propertyName = str_replace('general.', '', $key);

                return $originalSettings->$propertyName !== $value;
            }, ARRAY_FILTER_USE_BOTH);

            // Handle file uploads and update only if new files are uploaded
            $uploadedFiles = [];

            if (is_object($this->favicon)) {
                $faviconPath                      = $this->handleFileUpload($this->favicon, 'favicon');
                $uploadedFiles['general.favicon'] = $faviconPath;
            }

            if (is_object($this->site_dark_logo)) {
                $darkLogoPath                            = $this->handleFileUpload($this->site_dark_logo, 'site_dark_logo');
                $uploadedFiles['general.site_dark_logo'] = $darkLogoPath;
            }

            if (is_object($this->site_logo)) {
                $siteLogoPath                       = $this->handleFileUpload($this->site_logo, 'site_logo');
                $uploadedFiles['general.site_logo'] = $siteLogoPath;
            }

            if (is_object($this->cover_page_image)) {
                $siteLogoPath                              = $this->handleFileUpload($this->cover_page_image, 'cover_page_image');
                $uploadedFiles['general.cover_page_image'] = $siteLogoPath;
            }

            // Merge file uploads with modified settings
            $finalUpdates = array_merge($modifiedSettings, $uploadedFiles);

            // Save only if there are modifications
            if (! empty($finalUpdates)) {
                foreach ($finalUpdates as $key => $value) {
                    set_setting($key, $value);
                }

                $this->notify([
                    'type'    => 'success',
                    'message' => t('setting_save_successfully'),

                ]);
            }
        }
    }

    protected function handleFileUpload($file, $type)
    {
        create_storage_link();

        // Delete the old file based on the type
        if ($type === 'site_logo' && get_setting('general.site_logo') && file_exists('storage/' . get_setting('general.site_logo'))) {
            @unlink('storage/' . get_setting('general.site_logo'));
        }

        if ($type === 'site_dark_logo' && get_setting('general.site_dark_logo') && file_exists('storage/' . get_setting('general.site_dark_logo'))) {
            @unlink('storage/' . get_setting('general.site_dark_logo'));
        }

        if ($type === 'favicon' && get_setting('general.favicon') && file_exists('storage/' . get_setting('general.favicon'))) {
            @unlink('storage/' . get_setting('general.favicon'));
        }

        if ($type === 'cover_page_image' && get_setting('general.cover_page_image') && file_exists('storage/' . get_setting('general.cover_page_image'))) {
            @unlink('storage/' . get_setting('general.cover_page_image'));
        }

        // Generate a new filename and store the file
        $filename = $type . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('settings', $filename, 'public');

        return $path;
    }

    protected function removeSettingFile(string $pathSetting)
    {
        try {
            $filePath = get_setting($pathSetting);

            if ($filePath && file_exists(public_path('storage/' . $filePath))) {
                @unlink(public_path('storage/' . $filePath));
            }

            set_setting($pathSetting, null);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function removeSetting(string $type)
    {
        switch ($type) {
            case 'favicon':
                $this->removeSettingFile('general.favicon');
                $this->favicon = null;
                break;

            case 'site_logo':
                $this->removeSettingFile('general.site_logo');
                $this->site_logo = null;
                break;

            case 'site_dark_logo':
                $this->removeSettingFile('general.site_dark_logo');
                $this->site_dark_logo = null;
                break;

            case 'cover_page_image':
                $this->removeSettingFile('general.cover_page_image');
                $this->cover_page_image = null;
                break;
        }

        $this->notify([
            'type'    => 'success',
            'message' => ucfirst(str_replace('_', ' ', $type)) . t('remove_successfully'),
        ]);
    }

    public function render()
    {
        return view('livewire.admin.settings.system.general-settings');
    }
}
