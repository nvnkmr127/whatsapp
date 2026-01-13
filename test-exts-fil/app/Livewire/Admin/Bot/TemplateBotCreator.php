<?php

namespace App\Livewire\Admin\Bot;

use App\Models\TemplateBot;
use App\Models\WhatsappTemplate;
use App\Rules\PurifiedInput;
use App\Services\MergeFields;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class TemplateBotCreator extends Component
{
    use WithFileUploads;

    public $id;

    public $template_bot;

    public $template_name;

    public $rel_type;

    public $template_id;

    public $reply_type;

    public $is_bot_active = 1;

    public $trigger = [];

    public $headerInputs = [];

    public $bodyInputs = [];

    public $footerInputs = [];

    public $mergeFields;

    public $file;

    public $filename;

    public $isUploading = false;

    protected $listeners = [
        'save'            => 'save',
        'upload-started'  => 'setUploading',
        'upload-finished' => 'setUploadingComplete',
    ];

    public function mount()
    {
        if (! checkPermission(['template_bot.create', 'template_bot.edit'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }

        $this->id      = $this->getId();
        $templatebotId = request()->route('templatebotId');

        if ($templatebotId) {
            $this->template_bot  = TemplateBot::findOrFail($templatebotId);
            $this->template_name = $this->template_bot->name;
            $this->rel_type      = $this->template_bot->rel_type;
            $this->template_id   = $this->template_bot->template_id;
            $this->reply_type    = $this->template_bot->reply_type;
            $this->is_bot_active = $this->template_bot->is_bot_active;
            $this->trigger       = $this->template_bot->trigger ? array_filter(explode(',', $this->template_bot->trigger)) : [];
            $this->headerInputs  = json_decode($this->template_bot->header_params, true) ?? [];
            $this->bodyInputs    = json_decode($this->template_bot->body_params, true)   ?? [];
            $this->footerInputs  = json_decode($this->template_bot->footer_params, true) ?? [];
            $this->filename      = $this->template_bot->filename;
        } else {
            $this->template_bot = new TemplateBot;
        }

        $this->loadMergeFields($this->template_bot->rel_type ?? '');
    }

    protected function rules()
    {
        return [
            'template_name'  => ['required', 'string', 'max:100', new PurifiedInput(t('sql_injection_error'))],
            'rel_type'       => 'required|string|max:50',
            'template_id'    => 'required|integer|exists:whatsapp_templates,template_id',
            'reply_type'     => 'required|integer',
            'footerInputs.*' => [count($this->footerInputs) > 0 ? 'required' : 'nullable', new PurifiedInput(t('dynamic_input_error'))],
            'headerInputs.*' => [count($this->headerInputs) > 0 ? 'required' : 'nullable', new PurifiedInput(t('dynamic_input_error'))],
            'bodyInputs.*'   => [count($this->bodyInputs) > 0 ? 'required' : 'nullable',  new PurifiedInput(t('dynamic_input_error'))],
            'trigger'        => ($this->reply_type == 1 || $this->reply_type == 2) ? 'required' : 'nullable',
            'trigger.*'      => 'string|max:255|distinct',
            'file'           => 'nullable|file',

        ];
    }

    public function save()
    {
        if (checkPermission('template_bot.create', 'template_bot.edit')) {
            $this->validate();

            $template     = WhatsappTemplate::where('template_id', $this->template_id)->firstOrFail();
            $headerFormat = $template->header_data_format ?? 'TEXT';

            if ($headerFormat !== 'TEXT') {
                $this->validate([
                    'file' => array_merge([$this->filename ? 'nullable' : 'required', 'file'], $this->getFileValidationRules($headerFormat)),
                ]);
            }

            // File handling
            if ($this->file) {
                $this->handleFileUpload($headerFormat);
            }

            if ($template->header_params_count == 0) {
                $this->headerInputs = [];
            }

            if ($template->body_params_count == 0) {
                $this->bodyInputs = [];
            }

            if ($template->footer_params_count == 0) {
                $this->footerInputs = [];
            }

            // Update model properties
            $this->template_bot->fill([
                'name'          => $this->template_name,
                'rel_type'      => $this->rel_type,
                'template_id'   => $this->template_id,
                'reply_type'    => $this->reply_type,
                'is_bot_active' => $this->is_bot_active,
                'trigger'       => ($this->reply_type == 1 || $this->reply_type == 2) ? implode(',', $this->trigger) : null,
                'header_params' => json_encode(array_values(array_filter($this->headerInputs))),
                'body_params'   => json_encode(array_values(array_filter($this->bodyInputs))),
                'footer_params' => json_encode(array_values(array_filter($this->footerInputs))),
            ]);

            $this->template_bot->save();

            $this->notify(['type' => 'success', 'message' => t('template_bot_saved_successfully')], true);

            return redirect()->route('admin.templatebot.list');
        }
    }

    protected function getFileValidationRules($format)
    {
        return match ($format) {
            'IMAGE'    => ['mimes:jpeg,png', 'max:8192'],
            'DOCUMENT' => ['mimes:pdf,doc,docx,txt,ppt,pptx,xlsx,xls', 'max:102400'],
            'VIDEO'    => ['mimes:mp4,3gp', 'max:16384'],
            'AUDIO'    => ['mimes:mp3,wav,aac,ogg', 'max:16384'],
            default    => ['file', 'max:5120'],
        };
    }

    protected function handleFileUpload($format)
    {
        if ($this->template_bot->filename) {
            create_storage_link();
            Storage::disk('public')->delete($this->template_bot->filename);
        }

        $directory = match ($format) {
            'IMAGE'    => 'template_bot/images',
            'DOCUMENT' => 'template_bot/documents',
            'VIDEO'    => 'template_bot/videos',
            'AUDIO'    => 'template_bot/audio',
            default    => 'template_bot',
        };

        $this->template_bot->filename = $this->file->storeAs(
            $directory,
            $this->generateFileName(),
            'public'
        );
    }

    protected function generateFileName()
    {
        $original = str_replace(' ', '_', $this->file->getClientOriginalName());

        return pathinfo($original, PATHINFO_FILENAME) . '_' . time() . '.' . $this->file->extension();
    }

    public function getTemplatesProperty()
    {
        return WhatsappTemplate::get();
    }

    public function updatedRelType($value)
    {
        $this->loadMergeFields($value);
        $this->dispatch('templates-updated', $this->template_id);
    }

    public function loadMergeFields($group = '')
    {
        $mergeFieldsService = app(MergeFields::class);

        $field = array_merge(
            $mergeFieldsService->getFieldsForTemplate('other-group'),
            ! empty($group) ? $mergeFieldsService->getFieldsForTemplate('contact-group') : []
        );

        $this->mergeFields = json_encode(array_map(fn ($value) => [
            'key'   => ucfirst($value['name']),
            'value' => $value['key'],
        ], $field));
    }

    public function setUploading()
    {
        $this->isUploading = true;
    }

    public function setUploadingComplete()
    {
        $this->isUploading = false;
    }

    public function render()
    {
        return view('livewire.admin.bot.template-bot-creator');
    }
}
