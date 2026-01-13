<?php

namespace App\Livewire\Admin\Campaign;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Contact;
use App\Models\Source;
use App\Models\Status;
use App\Models\WhatsappTemplate;
use App\Rules\PurifiedInput;
use App\Services\MergeFields;
use App\Traits\WhatsApp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class CampaignCreator extends Component
{
    use WhatsApp, WithFileUploads;

    public $id;

    public $campaign;

    public $campaign_name;

    public $rel_type;

    public $campaign_id;

    public $template_id;

    public $relation_type_dynamic;

    public $scheduled_send_time;

    public $send_now = false;

    public $template_name = '';

    public $status_name = '';

    public $source_name = '';

    public $isChecked = false;

    public $contacts = [];

    public $selectedContacts = [];

    public $headerInputs = [];

    public $bodyInputs = [];

    public $footerInputs = [];

    public $file;

    public $filename;

    public $contactCount = 0;

    public $selected;

    public $header_params;

    public $body_params;

    public $footer_params;

    public $isDisconnected = false;

    public $isUploading = false;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'save'             => 'save',
        'upload-started'   => 'setUploading',
        'upload-finished'  => 'setUploadingComplete',
    ];

    public $mergeFields;

    protected $messages = [
        'rel_type.required'                     => 'Please select a relationship type',
        'rel_type.in'                           => 'Invalid relationship type selected',
        'relation_type_dynamic.required_unless' => 'Please select contacts for the campaign',
        'campaign_name.required'                => 'Campaign name is required',
        'campaign_name.min'                     => 'Campaign name must be at least 3 characters',
        'template_id.required'                  => 'Please select a template',
        'scheduled_send_time.required_if'       => 'Please set a schedule time for the campaign',
    ];

    protected function rules()
    {
        return [
            'rel_type'              => 'required|in:lead,customer',
            'relation_type_dynamic' => 'required_unless:isChecked,true',
            'headerInputs.*'        => [new PurifiedInput(t('dynamic_input_error'))],
            'bodyInputs.*'          => [new PurifiedInput(t('dynamic_input_error'))],
            'footerInputs.*'        => [new PurifiedInput(t('dynamic_input_error'))],
            'campaign_name'         => [
                'required',
                'min:3',
                'max:255',
                new PurifiedInput(t('sql_injection_error')),
            ],
            'template_id'         => 'required',
            'scheduled_send_time' => 'required_if:send_now,false',
            'file'                => 'nullable|file',
        ];
    }

    public function mount()
    {
        if (! checkPermission(['campaigns.create', 'campaigns.edit'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect()->route('admin.dashboard');
        } elseif (
            empty(get_setting('whatsapp.is_webhook_connected')) || empty(get_setting('whatsapp.is_whatsmark_connected')) || empty(get_setting('whatsapp.wm_default_phone_number'))
        ) {
            $this->isDisconnected = true;
        }

        $this->id   = $this->getId();
        $campaignId = request()->route('campaignId') ?? null;

        if ($campaignId) {
            $this->loadExistingCampaign($campaignId);
        } else {
            $this->initializeNewCampaign();
        }

        $this->loadMergeFields($this->campaign->rel_type);
    }

    public function loadMergeFields($group = '')
    {
        $mergeFieldsService = app(MergeFields::class);

        $field = array_merge(
            $mergeFieldsService->getFieldsForTemplate('other-group'),
            ! empty($group) ? $mergeFieldsService->getFieldsForTemplate('contact-group') : []
        );

        $this->reset('mergeFields');

        $this->mergeFields = json_encode(array_map(fn ($value) => [
            'key'   => ucfirst($value['name']),
            'value' => $value['key'],
        ], $field));
    }

    private function loadExistingCampaign($campaignId)
    {
        $this->campaign      = Campaign::findOrFail($campaignId);
        $this->campaign_id   = $this->campaign->id;
        $this->campaign_name = $this->campaign->name;
        $this->rel_type      = $this->campaign->rel_type;
        $this->template_id   = $this->campaign->template_id;
        $this->send_now      = $this->campaign->send_now;
        $this->isChecked     = $this->campaign->select_all;
        $this->contactCount  = $this->campaign->sending_count;
        $this->headerInputs  = json_decode($this->campaign->header_params, true) ?? [];
        $this->bodyInputs    = json_decode($this->campaign->body_params, true)   ?? [];
        $this->footerInputs  = json_decode($this->campaign->footer_params, true) ?? [];
        $this->filename      = $this->campaign->filename;

        // Handle scheduled_send_time when it's null
        $this->scheduled_send_time = $this->campaign->scheduled_send_time
            ? format_date_time($this->campaign->scheduled_send_time)
            : null;

        $relData           = json_decode($this->campaign->rel_data, true);
        $this->status_name = $relData['status_id'] ?? '';
        $this->source_name = $relData['source_id'] ?? '';

        $this->loadSelectedContacts($campaignId);
        $this->loadContacts();
    }

    private function loadSelectedContacts($campaignId)
    {
        $this->selected = Contact::whereIn('id', function ($query) use ($campaignId) {
            $query->select('rel_id')
                ->from('campaign_details')
                ->where('campaign_id', $campaignId);
        })
            ->where('type', function ($query) use ($campaignId) {
                $query->select('rel_type')
                    ->from('campaign_details')
                    ->where('campaign_id', $campaignId)
                    ->limit(1);
            })
            ->select('id', 'firstname', 'lastname', 'email', 'phone')
            ->orderBy('firstname')
            ->get();

        $this->relation_type_dynamic = $this->selected->pluck('id')->toArray();
    }

    private function initializeNewCampaign()
    {
        $this->campaign     = new Campaign;
        $this->send_now     = false;
        $this->isChecked    = false;
        $this->contactCount = 0;
    }

    public function updateContactCount($selectedContacts)
    {
        $this->contactCount = count($selectedContacts);
    }

    public function loadContacts()
    {
        if (! $this->rel_type) {
            $this->contacts     = [];
            $this->contactCount = 0;

            return;
        }

        $query = Contact::where('type', $this->rel_type);

        if (! $this->isChecked) {
            $this->applyFilters($query);
        }

        $this->contacts = $query->select('id', 'firstname', 'lastname', 'email', 'phone')
            ->where('is_enabled', 1)
            ->orderBy('firstname')
            ->get();

        // Dispatch event after contacts are loaded
        $this->dispatch('contacts-updated');

        if ($this->isChecked) {
            $this->countOfContacts();
        }
    }

    private function applyFilters($query)
    {
        if ($this->status_name) {
            $query->where('status_id', $this->status_name);
        }

        if ($this->source_name) {
            $query->where('source_id', $this->source_name);
        }

        return $query;
    }

    public function updatedStatusName()
    {
        $this->updateContactsBasedOnFilters();
    }

    public function updatedSourceName()
    {
        $this->updateContactsBasedOnFilters();
    }

    private function updateContactsBasedOnFilters()
    {
        if (! $this->isChecked) {
            $this->loadContacts();
        }

        $this->dispatch('contacts-updated', $this->contacts);
    }

    public function updatedIsChecked()
    {

        if ($this->isChecked) {
            $this->countOfContacts();
        } else {
            $this->contactCount = 0;
        }
    }

    public function countOfContacts()
    {
        if (! $this->rel_type) {
            $this->contactCount = 0;

            return;
        }

        $query = Contact::where('type', $this->rel_type);

        // Apply filters only when checkbox is not checked
        if (! $this->isChecked) {
            $this->applyFilters($query);

            $this->contacts = $query->select('id', 'firstname', 'lastname', 'email', 'phone')
                ->orderBy('firstname')
                ->get();
        }

        $this->contactCount = $query->count();
    }

    #[Computed]
    public function templates()
    {
        return WhatsappTemplate::query()->get();
    }

    #[Computed]
    public function statuses()
    {
        return Status::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function sources()
    {
        return Source::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function updatedRelType($value)
    {
        $this->relation_type_dynamic = [];
        $this->status_name           = '';
        $this->source_name           = '';
        $this->contacts              = [];
        $this->contactCount          = 0;

        if ($this->isChecked) {
            $this->countOfContacts();
        }

        $this->loadMergeFields($value);

        $this->loadContacts();

        $this->dispatch('contacts-updated', $this->contacts);
    }

    #[Computed]
    public function contactCount()
    {
        return is_array($this->relation_type_dynamic) ? count($this->relation_type_dynamic) : 0;
    }

    public function save()
    {
        if (checkPermission(['campaigns.create', 'campaigns.edit'])) {
            $this->validate();
            try {

                $template     = WhatsappTemplate::where('template_id', $this->template_id)->firstOrFail();
                $headerFormat = $template->header_data_format ?? 'TEXT';

                // Handle file validation
                if ($headerFormat !== 'TEXT') {
                    $this->validate([
                        'file' => array_merge([$this->filename ? 'nullable' : 'required', 'file'], $this->getFileValidationRules($headerFormat)),
                    ]);
                }

                // File handling
                if ($this->file) {
                    $this->handleFileUpload($headerFormat);
                }

                // Reset empty parameter arrays
                $this->resetEmptyParams($template);

                // Process scheduled time
                $scheduledTime = $this->processScheduledTime();
                if ($scheduledTime === false) {
                    return;
                }

                // Update campaign model
                $this->updateCampaignModel($scheduledTime);

                // Create campaign details
                $this->createCampaignDetails($template);

                $this->showSuccessNotification();

                $this->dispatch('saveCompleted');

                return redirect()->route('admin.campaigns.list');
            } catch (\Exception $e) {
                whatsapp_log('Error during campaign save: ' . $e->getMessage(), 'error', [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], $e);

                $this->notify([
                    'type'    => 'danger',
                    'message' => t('campaign_save_failed') . ': ' . $e->getMessage(),
                ], true);
            }
        }
    }

    private function resetEmptyParams($template)
    {
        if ($template->header_params_count == 0) {
            $this->headerInputs = [];
        }

        if ($template->body_params_count == 0) {
            $this->bodyInputs = [];
        }

        if ($template->footer_params_count == 0) {
            $this->footerInputs = [];
        }
    }

    private function processScheduledTime()
    {
        if ($this->send_now) {
            $scheduledTime = now();

            return $scheduledTime;
        }

        try {
            // Convert from "d-m-Y h:i A" (12-hour format) to "Y-m-d H:i:s" (24-hour format)
            $dateformat = get_setting('general.date_format');
            $timeformat = get_setting('general.time_format') == '12' ? 'h:i A' : 'h:i';
            $format     = $dateformat . ' ' . $timeformat;

            $scheduledTime = Carbon::createFromFormat($format, $this->scheduled_send_time)
                ->format('Y-m-d H:i:s');

            return $scheduledTime;
        } catch (\Exception $e) {
            $this->addError('scheduled_send_time', 'Invalid date/time format. Use DD-MM-YYYY HH:MM AM/PM');
            whatsapp_log('Error processing scheduled time: ' . $e->getMessage(), 'error', [
                'input_time' => $this->scheduled_send_time,
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ], $e);

            return false;
        }
    }

    private function updateCampaignModel($scheduledTime)
    {
        try {
            $this->header_params = json_encode(array_values(array_filter($this->headerInputs)));
            $this->body_params   = json_encode(array_values(array_filter($this->bodyInputs)));
            $this->footer_params = json_encode(array_values(array_filter($this->footerInputs)));

            $this->campaign = Campaign::updateOrCreate(
                ['id' => $this->campaign_id ?? null],
                [
                    'name'                => $this->campaign_name,
                    'rel_type'            => $this->rel_type,
                    'template_id'         => $this->template_id,
                    'scheduled_send_time' => $scheduledTime,
                    'send_now'            => $this->send_now,
                    'header_params'       => json_encode(array_values(array_filter($this->headerInputs))),
                    'body_params'         => json_encode(array_values(array_filter($this->bodyInputs))),
                    'footer_params'       => json_encode(array_values(array_filter($this->footerInputs))),
                    'select_all'          => $this->isChecked,
                    'sending_count'       => $this->isChecked ? $this->contactCount : count($this->relation_type_dynamic ?? []),
                    'filename'            => $this->campaign->filename ?? null, // Avoid error if $this->campaign is null
                    'rel_data'            => json_encode([
                        'status_id' => $this->status_name,
                        'source_id' => $this->source_name,
                    ]),
                ]
            );
        } catch (\Exception $e) {
            whatsapp_log('Error in updateCampaignModel: ' . $e->getMessage(), 'error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], $e);
            throw $e;
        }
    }

    private function createCampaignDetails($template)
    {
        if ($this->campaign->id) {
            $delete = CampaignDetail::where('campaign_id', $this->campaign->id)->delete();
        }

        if ($this->isChecked) {
            $this->createDetailsForAllContacts($template);
        } else {
            $this->createDetailsForSelectedContacts($template);
        }
    }

    private function createDetailsForAllContacts($template)
    {
        $contacts = Contact::where('type', $this->rel_type)->get();

        foreach ($contacts as $contact) {
            $this->createCampaignDetail($contact, $template);
        }
    }

    private function createDetailsForSelectedContacts($template)
    {
        $selectedContacts = Contact::whereIn('id', (array) $this->relation_type_dynamic)->get();

        foreach ($selectedContacts as $contact) {
            $this->createCampaignDetail($contact, $template);
        }
    }

    private function createCampaignDetail($contact, $template)
    {
        try {
            $template = $template->toArray();

            $campaignDetailId = CampaignDetail::insertGetId([
                'campaign_id'    => $this->campaign->id,
                'rel_id'         => $contact->id,
                'rel_type'       => $this->rel_type,
                'header_message' => $this->parseMessage(
                    $template['header_data_text'],
                    $this->headerInputs,
                    $contact
                ),
                'body_message' => $this->parseMessage(
                    $template['body_data'],
                    $this->bodyInputs,
                    $contact
                ),
                'footer_message' => $this->parseMessage(
                    $template['footer_data'],
                    $this->footerInputs,
                    $contact
                ),
                'status'           => $status['status'] ?? 1,
                'message_status'   => 'sent',
                'whatsapp_id'      => $status['whatsapp_id']      ?? null,
                'response_message' => $status['response_message'] ?? '',
            ]);
        } catch (\Exception $e) {
            whatsapp_log('Error in createCampaignDetail: ' . $e->getMessage(), 'error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], $e);
            throw $e;
        }
    }

    private function parseMessage($message, $params, $contact)
    {
        if (empty($message)) {
            return null;
        }

        $parsedMessage = $message;
        foreach ((array) $params as $index => $param) {
            $placeholder   = '{{' . ($index + 1) . '}}';
            $value         = $param ?? '';
            $parsedMessage = str_replace($placeholder, $value, $parsedMessage);
        }

        return $parsedMessage;
    }

    private function showSuccessNotification()
    {
        $this->notify([
            'type'    => 'success',
            'message' => $this->campaign->wasRecentlyCreated
                ? t('campaign_created_successfully')
                : t('campaign_update_successfully'),
        ], true);
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
        if ($this->campaign->filename) {
            create_storage_link();
            Storage::disk('public')->delete($this->campaign->filename);
        }

        $directory = match ($format) {
            'IMAGE'    => 'campaign/images',
            'DOCUMENT' => 'campaign/documents',
            'VIDEO'    => 'campaign/videos',
            'AUDIO'    => 'campaign/audio',
            default    => 'campaign',
        };

        $this->campaign->filename = $this->file->storeAs(
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
        if ($this->isDisconnected) {
            return <<<'blade'
                    <x-account-disconnected />
            blade;
        }

        return view('livewire.admin.campaign.campaign-creator');
    }
}
