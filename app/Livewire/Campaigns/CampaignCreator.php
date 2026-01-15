<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Contact;
use App\Models\WhatsappTemplate;
use App\Traits\WhatsApp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Validator;

class CampaignCreator extends Component
{
    use WhatsApp, WithFileUploads;

    public $campaign_name;
    public $rel_type = 'contact';
    public $template_id;
    public $scheduled_send_time;
    public $send_now = false;

    // Dynamic Inputs
    public $headerInputs = [];
    public $bodyInputs = [];
    public $footerInputs = [];

    // File Upload
    public $file;
    public $filename;

    // Contacts Selection
    public $contacts = []; // For TomSelect options
    public $relation_type_dynamic = []; // Selected IDs
    public $isChecked = false; // Select All toggle
    public $contactCount = 0;

    // UI Helpers
    public $mergeFields;
    public $isUploading = false;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'upload-started' => 'setUploading',
        'upload-finished' => 'setUploadingComplete',
        'contacts-updated' => '$refresh'
    ];

    public function mount()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-campaigns');

        $this->scheduled_send_time = now()->format('Y-m-d H:i');

        // Initialize Merge Fields for Tribute.js
        $this->mergeFields = json_encode([
            ['key' => 'First Name', 'value' => 'firstname'],
            ['key' => 'Last Name', 'value' => 'lastname'],
            ['key' => 'Phone', 'value' => 'phone'],
        ]);

        // Load initial contact options (limit to 50 for performance, TomSelect handles search)
        $this->loadContacts();
    }

    public function loadContacts()
    {
        $this->contacts = Contact::where('team_id', auth()->user()->current_team_id)
            ->limit(100) // Limit for initial load
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'firstname' => $c->first_name,
                    'lastname' => $c->last_name,
                    'phone' => $c->phone
                ];
            });
    }

    public function updatedTemplateId($value)
    {
        $template = WhatsappTemplate::where('team_id', auth()->user()->current_team_id)->find($value);

        if ($template) {
            $this->headerInputs = array_fill(0, $template->header_params_count ?? 0, '');
            $this->bodyInputs = array_fill(0, $template->body_params_count ?? 0, '');
            $this->footerInputs = array_fill(0, $template->footer_params_count ?? 0, '');
            $this->file = null;
            $this->filename = null;
        }
    }

    #[Computed]
    public function templates()
    {
        return WhatsappTemplate::where('team_id', auth()->user()->current_team_id)->where('status', 'APPROVED')->get();
    }

    // Toggle "Select All"
    public function updatedIsChecked($value)
    {
        if ($value) {
            $this->contactCount = Contact::where('team_id', auth()->user()->current_team_id)->count();
            // Clear specific selection because "All" overrides it
            $this->relation_type_dynamic = [];
        } else {
            $this->contactCount = 0;
        }
    }

    public function updateContactCount($selectedIds)
    {
        // Called from frontend specific selection
        $this->relation_type_dynamic = $selectedIds;
        $this->contactCount = count($selectedIds);
    }

    public function save()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-campaigns');
        $this->validate([
            'campaign_name' => 'required|min:3',
            'template_id' => 'required',
            'scheduled_send_time' => 'required_unless:send_now,true',
            'relation_type_dynamic' => 'required_without:isChecked', // Require specific contacts if "All" is not checked check logic
        ]);

        try {
            $template = WhatsappTemplate::where('team_id', auth()->user()->current_team_id)->findOrFail($this->template_id);
            $headerFormat = $template->header_data_format ?? 'TEXT';

            // Handle File
            if ($this->file) {
                $this->validate(['file' => 'required|file|max:10240']); // 10MB
                $this->filename = $this->file->store('campaigns', 'public');
            }

            // Create Campaign
            $campaign = Campaign::create([
                'team_id' => auth()->user()->current_team_id,
                'name' => $this->campaign_name,
                'template_id' => $template->template_id,
                'template_name' => $template->template_name,
                'scheduled_send_time' => $this->send_now ? now() : Carbon::parse($this->scheduled_send_time),
                'status' => $this->send_now ? 'processing' : 'scheduled',
                'header_params' => json_encode($this->headerInputs),
                'body_params' => json_encode($this->bodyInputs),
                'footer_params' => json_encode($this->footerInputs),
                'filename' => $this->filename,
                'total_contacts' => $this->isChecked ? Contact::where('team_id', auth()->user()->current_team_id)->count() : count($this->relation_type_dynamic),
            ]);

            // Create Campaign Details (Recipients)
            $query = Contact::where('team_id', auth()->user()->current_team_id);
            if (!$this->isChecked) {
                $query->whereIn('id', $this->relation_type_dynamic);
            }

            $query->chunk(100, function ($contacts) use ($campaign) {
                $details = [];
                foreach ($contacts as $contact) {
                    $details[] = [
                        'campaign_id' => $campaign->id,
                        'rel_id' => $contact->id,
                        'rel_type' => 'contact',
                        'phone' => $contact->phone,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                CampaignDetail::insert($details);
            });

            $this->dispatch('notify', 'Campaign created successfully!');
            return redirect()->route('campaigns.index');

        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
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
        return view('livewire.campaigns.campaign-creator');
    }
}
