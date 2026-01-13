<?php

namespace App\Livewire\Admin\Settings\Whatsmark;

use App\Rules\PurifiedInput;
use App\Traits\Ai;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AiIntegrationSettings extends Component
{
    use Ai;

    public $id;

    public ?bool $enable_openai_in_chat = false;

    public ?string $openai_secret_key = '';

    public ?string $chat_model = '';

    public ?array $chatGptModels = [];

    private array $keys = [
        'enable_openai_in_chat' => false,
        'openai_secret_key'     => '',
        'chat_model'            => '',
    ];

    protected function rules()
    {
        return [
            'enable_openai_in_chat' => 'nullable|boolean',

            'openai_secret_key' => [
                'nullable',
                'string',
                'max:255',
                new PurifiedInput(t('sql_injection_error')),
                'required_if:enable_openai_in_chat,true',
            ],

            'chat_model' => [
                'nullable',
                'string',
                Rule::in(array_column($this->chatGptModels, 'id')),
                'required_if:enable_openai_in_chat,true',
            ],
        ];
    }

    public function mount()
    {
        if (! checkPermission('whatsmark_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->id = $this->getId();
        $this->loadSettings();

        $this->chatGptModels = config('aimodel.models');
    }

    protected function loadSettings()
    {
        $settings = get_settings_by_group('whats-mark');

        $this->enable_openai_in_chat = $settings->enable_openai_in_chat ?? false;
        $this->openai_secret_key     = $settings->openai_secret_key;
        $this->chat_model            = $settings->chat_model;
    }

    public function save()
    {
        if (checkPermission('whatsmark_settings.edit')) {
            $this->validate();

            $originalSettings = get_settings_by_group('whats-mark');

            $newSettings = [
                'enable_openai_in_chat' => $this->enable_openai_in_chat,
                'openai_secret_key'     => $this->openai_secret_key,
                'chat_model'            => $this->chat_model,
            ];

            // Filter the settings that have been modified
            $modifiedSettings = array_filter($newSettings, function ($value, $key) use ($originalSettings) {
                return $originalSettings->$key !== $value;
            }, ARRAY_FILTER_USE_BOTH);

            // Save only modified settings
            if (! empty($modifiedSettings)) {
                // If OpenAI secret key is changed or not verified, update it and verify
                if (isset($this->openai_secret_key) && (get_setting('whats-mark.openai_secret_key') != $this->openai_secret_key || get_setting('whats-mark.is_open_ai_key_verify') == false)) {
                    set_setting('whats-mark.openai_secret_key', $this->openai_secret_key);
                    $response = $this->listModel();
                    if (! $response['status']) {
                        $this->notify(['type' => 'danger', 'message' => $response['message']]);

                        return;
                    }
                }
                set_settings_batch('whats-mark', $modifiedSettings);
                $this->notify(['type' => 'success', 'message' => t('setting_save_successfully')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.whatsmark.ai-integration-settings');
    }
}
