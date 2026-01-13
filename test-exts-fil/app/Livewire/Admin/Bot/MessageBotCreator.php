<?php

namespace App\Livewire\Admin\Bot;

use App\Models\MessageBots;
use App\Rules\PurifiedInput;
use App\Services\MergeFields;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class MessageBotCreator extends Component
{
    use WithFileUploads;

    // Model instance
    public MessageBots $message_bot;

    public $id;

    public $message_bot_id;

    // Form fields
    public $bot_name;

    public $relation_type;

    public $reply_text = '';

    public $reply_type = '';

    public $trigger_keyword = [];

    public $tag = '';

    public $header = '';

    public $footer = '';

    // Button options
    public $button1 = '';

    public $button1_id = '';

    public $button2 = '';

    public $button2_id = '';

    public $button3 = '';

    public $button3_id = '';

    // Call-to-Action options
    public $button_name = '';

    public $button_link;

    // File upload
    public $file_upload;

    public $extensions;

    public $wmReplyType;

    public $mergeFields;

    public $fileType;

    public $isUploading = false;

    protected $listeners = [
        'upload-started'  => 'setUploading',
        'upload-finished' => 'setUploadingComplete',
    ];

    public function mount($messagebotId = null)
    {
        if (! checkPermission(['message_bot.create', 'message_bot.edit'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->id            = $this->getId();
        $this->extensions    = get_meta_allowed_extension();
        $this->relation_type = array_key_first(\App\Enums\WhatsAppTemplateRelationType::getRelationType());
        $this->reply_type    = array_key_first(\App\Enums\WhatsAppTemplateRelationType::getReplyType());
        $this->fileType      = 'image';
        if ($messagebotId) {
            $this->message_bot = MessageBots::find($messagebotId);

            // Check if message_bot was found before accessing its properties
            if ($this->message_bot) {
                $this->message_bot_id = $messagebotId;
                $this->loadTemplateData();
            } else {
                // Handle the case where no message_bot was found for the provided ID
                // For example, you can initialize an empty bot or show an error
                $this->message_bot = new MessageBots;
            }
        } else {
            $this->message_bot = new MessageBots;
        }
        $this->loadMergeFields();
    }

    public function setUploading()
    {
        $this->isUploading = true;
    }

    public function setUploadingComplete()
    {
        $this->isUploading = false;
    }

    public function loadMergeFields()
    {
        $mergeFieldsService = app(MergeFields::class);

        $field = array_merge(
            $mergeFieldsService->getFieldsForTemplate('other-group'),
            $mergeFieldsService->getFieldsForTemplate('contact-group')
        );

        $this->mergeFields = json_encode(array_map(fn ($value) => [
            'key'   => ucfirst($value['name']),
            'value' => $value['key'],
        ], $field));
    }

    public function loadTemplateData()
    {
        if ($this->message_bot) {
            $this->bot_name        = $this->message_bot->name;
            $this->relation_type   = $this->message_bot->rel_type;
            $this->reply_text      = $this->message_bot->reply_text;
            $this->reply_type      = $this->message_bot->reply_type;
            $this->trigger_keyword = $this->message_bot->trigger ? array_filter(explode(',', $this->message_bot->trigger)) : [];

            $this->header      = $this->message_bot->bot_header;
            $this->footer      = $this->message_bot->bot_footer;
            $this->button1     = $this->message_bot->button1;
            $this->button1_id  = $this->message_bot->button1_id;
            $this->button2     = $this->message_bot->button2;
            $this->button2_id  = $this->message_bot->button2_id;
            $this->button3     = $this->message_bot->button3;
            $this->button3_id  = $this->message_bot->button3_id;
            $this->button_name = $this->message_bot->button_name;
            $this->button_link = $this->message_bot->button_url;
            $this->file_upload = $this->message_bot->filename;

            if ($this->file_upload) {
                $extension = pathinfo($this->file_upload, PATHINFO_EXTENSION);
                $extension = strtolower($extension);

                if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])) {
                    $this->fileType = 'document';
                } elseif (in_array($extension, ['mp4', '3gp'])) {
                    $this->fileType = 'video';
                } else {
                    $this->fileType = 'image';
                }
            } else {
                $this->fileType = 'image';
            }
        }
    }

    public function validateReplyText()
    {
        $this->validate([
            'reply_text' => ['required', 'string', new PurifiedInput(t('sql_injection_error'))],
        ]);
    }

    public function rules()
    {
        // Define dynamic MIME types and size based on content type
        $fileRules = ['nullable'];

        if ($this->file_upload instanceof \Illuminate\Http\UploadedFile) {
            $mimeTypes = [
                'image'    => 'mimes:jpeg,png|max:5120',
                'document' => 'mimes:pdf,doc,docx,txt,xls,xlsx,ppt,pptx|max:102400',
                'video'    => 'mimes:mp4,3gp|max:16384',
            ];

            // Detect file type by MIME and apply appropriate rules
            $fileMime = $this->file_upload->getMimeType();

            switch (true) {
                case str_contains($fileMime, 'image'):
                    $fileRules[] = $mimeTypes['image'];
                    break;

                case str_contains($fileMime, 'pdf') || str_contains($fileMime, 'msword') || str_contains($fileMime, 'officedocument'):
                    $fileRules[] = $mimeTypes['document'];
                    break;

                case str_contains($fileMime, 'video'):
                    $fileRules[] = $mimeTypes['video'];
                    break;
            }
        }

        return [
            'bot_name'        => ['required', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'relation_type'   => 'required|string|max:50',
            'reply_text' => ['required', 'string', new PurifiedInput(t('sql_injection_error'))],
            'reply_type'      => 'required|max:1024',
            'trigger_keyword' => ($this->reply_type == 1 || $this->reply_type == 2) ? 'required' : 'nullable',
            'button_link'     => ['nullable', 'url', 'max:255', new PurifiedInput(t('sql_injection_error'))],
            'button1'         => ['nullable', 'max:20', new PurifiedInput(t('sql_injection_error'))],
            'button2'         => ['nullable', 'max:20', new PurifiedInput(t('sql_injection_error'))],
            'button3'         => ['nullable', 'max:20', new PurifiedInput(t('sql_injection_error'))],
            'button_name'     => ['nullable', 'max:20', new PurifiedInput(t('sql_injection_error'))],
            'header'          => ['nullable', 'max:60', new PurifiedInput(t('sql_injection_error'))],
            'footer'          => ['nullable', 'max:60', new PurifiedInput(t('sql_injection_error'))],
            'button1_id'      => ['nullable', 'max:256', new PurifiedInput(t('sql_injection_error'))],
            'button2_id'      => ['nullable', 'max:256', new PurifiedInput(t('sql_injection_error'))],
            'button3_id'      => ['nullable', 'max:256', new PurifiedInput(t('sql_injection_error'))],
            'file_upload'     => $fileRules,
        ];
    }

    public function save()
    {
        if (checkPermission('message_bot.create', 'message_bot.edit')) {
            $this->validate($this->rules());

            try {
                create_storage_link();
                if ($this->file_upload && is_array($this->file_upload)) {
                    foreach ($this->file_upload as $file) {
                        $originalName = str_replace(' ', '_', $file->getClientOriginalName());
                        $uniqueName   = time() . '_' . $originalName;
                        $file_path    = $file->storeAs('bot_files', $uniqueName, 'public');
                    }
                } elseif (is_string($this->file_upload)) {
                    $file_path = $this->file_upload;
                } else {
                    $file_path = null;
                }

                $this->message_bot->fill([
                    'name'        => $this->bot_name,
                    'rel_type'    => $this->relation_type,
                    'reply_text'  => $this->reply_text,
                    'reply_type'  => $this->reply_type,
                    'trigger'     => ($this->reply_type == 1 || $this->reply_type == 2) ? implode(',', $this->trigger_keyword) : null,
                    'bot_header'  => $this->header,
                    'bot_footer'  => $this->footer,
                    'button1'     => $this->button1,
                    'button1_id'  => $this->button1_id,
                    'button2'     => $this->button2,
                    'button2_id'  => $this->button2_id,
                    'button3'     => $this->button3,
                    'button3_id'  => $this->button3_id,
                    'button_name' => $this->button_name,
                    'button_url'  => $this->button_link,
                    'addedfrom'   => 1,
                    'filename'    => $file_path,
                ]);

                $this->message_bot->save();

                $this->notify(['type' => 'success', 'message' => t('message_bot_saved_successfully')], true);

                return redirect(route('admin.messagebot.list'));
            } catch (\Exception $e) {
                whatsapp_log('MessageBot Save Error', 'error', ['bot_name' => $this->bot_name], $e);
                $this->notify(['type' => 'danger', 'message' => t('message_bot_save_failed')]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.bot.message-bot-creator');
    }
}