<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use App\Models\ContactTag;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ContactManager extends Component
{
    use \Livewire\WithFileUploads;
    use WithPagination;
    use \App\Traits\HasFlashMessages;

    public $search = '';

    public $filterTag = '';

    public $filterStatus = '';

    // Modal state
    public $isModalOpen = false;

    public $isDeleteModalOpen = false;

    public $contactId;

    // Form fields
    public $name;

    public $countryCode;

    public $phoneNumberWithoutCode;

    public $email;

    public $language = 'en';

    public $opt_in_status = 'opted_in';

    public $category_id;

    public $selectedTags = [];

    public $customAttributes = []; // For holding dynamic field values

    // Rich View Data
    public $activeTab = 'profile';
    public $timeline = [];
    public $mediaVault = [];
    public $heatmap = [];

    public $availableCountryCodes = [
        '+1' => 'United States/Canada (+1)',
        '+20' => 'Egypt (+20)',
        '+44' => 'United Kingdom (+44)',
        '+61' => 'Australia (+61)',
        '+91' => 'India (+91)',
        '+964' => 'Iraq (+964)',
        '+966' => 'Saudi Arabia (+966)',
        '+971' => 'UAE (+971)',
    ];

    // Import State
    public $isImportModalOpen = false;

    public $importFile;

    public $csvHeaders = [];

    public $columnMapping = []; // csv_header => system_field

    #[Locked]
    public $importResult = null;

    #[Locked]
    public $importPreview = null;

    public $isDuplicateModalOpen = false;

    public $importDuplicateStrategy = 'update';

    public $importDuplicateTagStrategy = 'add';

    public $isImporting = false;

    public $importDuplicatesConfirmed = false;

    // Custom Fields State
    public $isFieldModalOpen = false;

    public $fieldId;

    public $fieldLabel;

    public $fieldType = 'text';

    public $fieldOptions = ''; // Comma separated for editing

    // Merge State
    public $isMergeModalOpen = false;

    public $sourceContactId;

    public $targetContactId;

    #[Locked]
    public $sourceContact;

    #[Locked]
    public $targetContact;

    public $isMerging = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterTag' => ['except' => ''],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'countryCode' => 'required|string',
        'phoneNumberWithoutCode' => 'required|string|max:20',
        'email' => 'nullable|email|max:255',
        'language' => 'required|string|max:10',
        'opt_in_status' => 'required|in:opted_in,opted_out',
        'category_id' => 'nullable|exists:categories,id',
        'selectedTags' => 'nullable',

        'customAttributes' => 'array',
    ];

    #[Layout('components.layouts.app')]
    public function render()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-contacts');
        $teamId = Auth::user()->currentTeam->id;
        $query = Contact::query()->where('team_id', $teamId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone_number', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterTag) {
            $query->whereHas('tags', function ($q) {
                $q->where('contact_tags.id', $this->filterTag);
            });
        }

        if ($this->filterStatus) {
            $query->where('opt_in_status', $this->filterStatus);
        }

        $contacts = $query
            ->with(['tags', 'category'])
            ->withCount(['messages', 'conversations', 'tags'])
            ->latest()
            ->paginate(15);
        $tags = \Illuminate\Support\Facades\Cache::remember("team_{$teamId}_tags", 60, function() use ($teamId) {
            return ContactTag::where('team_id', $teamId)->get();
        });
        $customFields = \Illuminate\Support\Facades\Cache::remember("team_{$teamId}_fields", 60, function() use ($teamId) {
            return \App\Models\ContactField::where('team_id', $teamId)->get();
        });
        $categories = \Illuminate\Support\Facades\Cache::remember("team_{$teamId}_categories", 60, function() use ($teamId) {
            return \App\Models\Category::where('team_id', $teamId)
                ->whereIn('target_module', ['all', 'contacts'])
                ->get();
        });

        return view('livewire.contacts.contact-manager', compact('contacts', 'tags', 'customFields', 'categories'))->layout('layouts.app');
    }

    public function create()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-contacts');
        $this->resetInputFields();
        // Load default country code from settings
        $this->countryCode = get_setting('default_country_code', '+91');
        $this->openModal();
    }

    // View Modal State
    public $isViewModalOpen = false;

    #[Locked]
    public $viewingContact = null;

    public function viewContact($id)
    {
        $this->viewingContact = Contact::with(['tags', 'category'])
            ->withCount(['messages', 'conversations', 'tags'])
            ->findOrFail($id);

        $this->loadRichData($this->viewingContact);
        $this->isViewModalOpen = true;
    }

    protected function loadRichData(Contact $contact)
    {
        $service = app(\App\Services\ContactTimelineService::class);
        
        $this->timeline = $service->getTimeline($contact);
        $this->mediaVault = $service->getMediaVault($contact);
        $this->heatmap = $service->getInteractionHeatmap($contact);
    }

    public function closeViewModal()
    {
        $this->isViewModalOpen = false;
        $this->viewingContact = null;
        $this->activeTab = 'profile';
        
        // Clear rich data to reduce Livewire payload
        $this->timeline = [];
        $this->mediaVault = [];
        $this->heatmap = [];
    }

    public function edit($id)
    {
        // Close view modal if open
        $this->isViewModalOpen = false;
        $this->viewingContact = null;

        $contact = Contact::findOrFail($id);
        $this->contactId = $id;
        $this->name = $contact->name;

        // Split phone number into country code and number
        $phone = $contact->phone_number;
        // Try to extract country code (assuming format like 918686877397 or +918686877397)
        $phone = ltrim($phone, '+');

        // Check if phone starts with known country code
        $foundCode = null;
        foreach (array_keys($this->availableCountryCodes) as $code) {
            $codeWithoutPlus = ltrim($code, '+');
            if (str_starts_with($phone, $codeWithoutPlus)) {
                $foundCode = $code;
                $this->phoneNumberWithoutCode = substr($phone, strlen($codeWithoutPlus));
                break;
            }
        }

        if ($foundCode) {
            $this->countryCode = $foundCode;
        } else {
            // Fallback to default country code
            $this->countryCode = get_setting('default_country_code', '+91');
            $this->phoneNumberWithoutCode = $phone;
        }

        $this->email = $contact->email;
        $this->language = $contact->language;
        $this->opt_in_status = $contact->opt_in_status;
        $this->category_id = $contact->category_id;
        $this->selectedTags = $contact->tags->pluck('id')->toArray();
        $this->customAttributes = $contact->custom_attributes ?? [];
        $this->openModal();
    }

    public function store()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-contacts');
        $this->validate();

        try {
            // Combine country code + phone number
            $fullPhoneNumber = $this->countryCode.ltrim($this->phoneNumberWithoutCode, '0');

            $data = [
                'team_id' => Auth::user()->currentTeam->id,
                'name' => $this->name,
                'phone_number' => $fullPhoneNumber,
                'email' => $this->email,
                'language' => $this->language,
                'opt_in_status' => $this->opt_in_status,
                'category_id' => $this->category_id ?: null,
                'custom_attributes' => $this->customAttributes,
                'force_name_update' => true,
            ];

            if ($this->contactId) {
                $data['id'] = $this->contactId;
            } else {
                $entitlement = app(\App\Services\EntitlementService::class)->for(Auth::user()->currentTeam);
                if (! $entitlement->can('add_contact')) {
                    $this->error('Contact limit reached: ' . $entitlement->denialReason('add_contact'));

                    return;
                }
            }

            $contactService = new \App\Services\ContactService;
            $contact = $contactService->createOrUpdate($data);

            $tagsToSync = is_array($this->selectedTags) ? $this->selectedTags : (empty($this->selectedTags) ? [] : [$this->selectedTags]);
            $contact->tags()->sync($tagsToSync);

            audit(
                $this->contactId ? 'contact.updated' : 'contact.created',
                ($this->contactId ? 'Updated' : 'Created')." contact '{$contact->name}' ({$contact->phone_number})",
                $contact
            );

            $this->success($this->contactId ? 'Contact updated successfully.' : 'Contact created successfully.');
            $this->closeModal();
            $this->resetInputFields();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Contact Store Failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->error('Error saving contact: '.$e->getMessage());
        }
    }

    public function confirmDelete($id)
    {
        $this->contactId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if ($this->contactId) {
            $contact = Contact::find($this->contactId);
            if ($contact) {
                $contact->delete();
                audit('contact.deleted', "Deleted contact '{$contact->name}' ({$contact->phone_number})", $contact);
                $this->success('Contact deleted successfully.');
            }
        }
        $this->isDeleteModalOpen = false;
        $this->resetInputFields();
    }

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    // Tag Management (Now handled by child component)
    public function openTagModal()
    {
        $this->dispatch('openTagManager')->to(TagManager::class);
    }

    // Custom Fields Management
    public function openFieldModal()
    {
        $this->resetFieldInput();
        $this->isFieldModalOpen = true;
    }

    public function editField($id)
    {
        $field = \App\Models\ContactField::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->fieldId = $id;
        $this->fieldLabel = $field->label;
        $this->fieldType = $field->type;
        $this->fieldOptions = $field->options ? implode(', ', $field->options) : '';
        $this->isFieldModalOpen = true;
    }

    public function storeField()
    {
        $this->validate([
            'fieldLabel' => 'required|string|max:50',
            'fieldType' => 'required|in:text,number,date,select',
        ]);

        $key = \Illuminate\Support\Str::slug($this->fieldLabel, '_');
        $options = $this->fieldType === 'select' ? array_map('trim', explode(',', $this->fieldOptions)) : null;

        \App\Models\ContactField::updateOrCreate(
            ['id' => $this->fieldId],
            [
                'team_id' => Auth::user()->currentTeam->id,
                'label' => $this->fieldLabel,
                'key' => $this->fieldId ? \App\Models\ContactField::find($this->fieldId)->key : $key, // Don't change key on edit
                'type' => $this->fieldType,
                'options' => $options,
            ]
        );

        $this->isFieldModalOpen = false;
        $this->resetFieldInput();
        session()->flash('field_message', 'Custom Field saved successfully.');
    }

    public function deleteField($id)
    {
        \App\Models\ContactField::where('team_id', Auth::user()->currentTeam->id)->where('id', $id)->delete();
        session()->flash('field_message', 'Custom Field deleted.');
    }

    // Merge Management
    public function openMergeModal($sourceId)
    {
        $this->sourceContactId = $sourceId;
        $this->sourceContact = Contact::findOrFail($sourceId);
        $this->targetContactId = null;
        $this->targetContact = null;
        $this->isMergeModalOpen = true;
    }

    public function updatedTargetContactId($value)
    {
        if ($value) {
            $this->targetContact = Contact::find($value);
        } else {
            $this->targetContact = null;
        }
    }

    public function merge()
    {
        $this->validate([
            'targetContactId' => 'required|exists:contacts,id|different:sourceContactId',
        ], [
            'targetContactId.different' => 'Cannot merge a contact into itself.',
        ]);

        $this->isMerging = true;

        try {
            $service = new \App\Services\ContactMergeService;
            $source = Contact::findOrFail($this->sourceContactId);
            $target = Contact::findOrFail($this->targetContactId);

            $service->merge($target, $source, Auth::id());

            $this->isMergeModalOpen = false;
            $this->resetMergeState();
            session()->flash('message', "{$source->name} successfully merged into {$target->name}.");

            // Redirect or refresh
            return redirect()->route('contacts.index');

        } catch (\Exception $e) {
            session()->flash('merge_error', 'Error merging contacts: '.$e->getMessage());
        } finally {
            $this->isMerging = false;
        }
    }

    public function resetMergeState()
    {
        $this->sourceContactId = null;
        $this->targetContactId = null;
        $this->sourceContact = null;
        $this->targetContact = null;
    }

    private function resetFieldInput()
    {
        $this->fieldId = null;
        $this->fieldLabel = '';
        $this->fieldType = 'text';
        $this->fieldOptions = '';
    }

    // Import Management
    public function openImportModal()
    {
        $this->isImportModalOpen = true;
        $this->importResult = null;
        $this->importPreview = null;
        $this->isDuplicateModalOpen = false;
        $this->importDuplicateStrategy = 'update';
        $this->importDuplicateTagStrategy = 'add';
        $this->importDuplicatesConfirmed = false;
        $this->importFile = null;
        $this->csvHeaders = [];
        $this->columnMapping = [];
        $this->isImporting = false;
    }

    public function closeImportModal()
    {
        $this->isImportModalOpen = false;
    }

    public function updatedImportFile()
    {
        $this->validate([
            'importFile' => 'required|mimes:csv,txt,xlsx,xls,ods|max:10240',
        ]);

        $service = new \App\Services\ContactImportService(Auth::user()->currentTeam);
        $this->csvHeaders = $service->getHeaders($this->importFile->getRealPath());

        // Auto-map common fields
        foreach ($this->csvHeaders as $header) {
            $lowerHeader = strtolower($header);
            if (in_array($lowerHeader, ['name', 'phone', 'phone_number', 'email', 'tags'])) {
                $target = $lowerHeader === 'phone' ? 'phone_number' : $lowerHeader;
                $this->columnMapping[$header] = $target;
            }
        }

        $this->importPreview = null;
    }

    public function import()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-contacts');
        $this->validate([
            'importFile' => 'required',
            'columnMapping' => 'required|array',
        ]);

        $importService = new \App\Services\ContactImportService(Auth::user()->currentTeam);
        $this->isImporting = true;

        try {
            if (! $this->importPreview) {
                $preview = $importService->previewImport($this->importFile->getRealPath(), $this->columnMapping);

                // Limit preview duplicates to prevent hitting post_max_size on next request
                if (count($preview['duplicates_existing'] ?? []) > 100) {
                    $preview['duplicates_existing_count'] = count($preview['duplicates_existing']);
                    $preview['duplicates_existing'] = array_slice($preview['duplicates_existing'], 0, 100);
                }

                if (count($preview['duplicates_in_file'] ?? []) > 100) {
                    $preview['duplicates_in_file_count'] = count($preview['duplicates_in_file']);
                    $preview['duplicates_in_file'] = array_slice($preview['duplicates_in_file'], 0, 100);
                }

                $this->importPreview = $preview;
            }

            $hasDuplicates = ! empty($this->importPreview['duplicates_existing'] ?? []) || ! empty($this->importPreview['duplicates_in_file'] ?? []);

            if ($hasDuplicates && ! $this->importDuplicatesConfirmed) {
                $this->isDuplicateModalOpen = true;

                return;
            }

            $result = $importService->import($this->importFile->getRealPath(), $this->columnMapping, [
                'on_duplicate' => $this->importDuplicateStrategy,
                'on_duplicate_tag' => $this->importDuplicateTagStrategy,
            ]);
        } finally {
            $this->isImporting = false;
        }

        $this->importResult = [
            'total' => $result['success_count'] + ($result['skipped_count'] ?? 0) + count($result['errors']),
            'imported' => $result['success_count'],
            'created' => $result['created_count'] ?? null,
            'updated' => $result['updated_count'] ?? null,
            'skipped' => $result['skipped_count'] ?? null,
            'errors' => array_slice($result['errors'], 0, 100), // Limit error list
            'error_count' => count($result['errors']),
        ];

        if ($result['success_count'] > 0) {
            audit('contact.imported', "Imported {$result['success_count']} contacts from file.", null, null, ['result' => $result]);
            session()->flash('import_message', "Imported {$result['success_count']} contacts successfully.");
            // Reset state to avoid issues
            $this->importFile = null;
            $this->importPreview = null;
        }
    }

    public function confirmImportAfterDuplicates()
    {
        $this->isDuplicateModalOpen = false;
        $this->importDuplicatesConfirmed = true;
        $this->import();
        $this->importDuplicatesConfirmed = false;
    }

    public function cancelImportAfterDuplicates()
    {
        $this->isDuplicateModalOpen = false;
        $this->importDuplicatesConfirmed = false;
    }

    // Alias for the old method name if any other component called it
    public function export()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-contacts');

        $query = Contact::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone_number', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterTag) {
            $query->whereHas('tags', function ($q) {
                $q->where('contact_tags.id', $this->filterTag);
            });
        }

        if ($this->filterStatus) {
            $query->where('opt_in_status', $this->filterStatus);
        }

        $contacts = $query->with(['tags', 'category'])->get();
        $customFields = \App\Models\ContactField::where('team_id', Auth::user()->currentTeam->id)->get();

        $callback = function () use ($contacts, $customFields) {
            $file = fopen('php://output', 'w');

            // Headers
            $headers = ['Name', 'Phone Number', 'Email', 'Opt-in Status', 'Language', 'Category', 'Tags'];
            foreach ($customFields as $field) {
                $headers[] = $field->label;
            }
            fputcsv($file, $headers);

            // Data
            foreach ($contacts as $contact) {
                $row = [
                    $contact->name,
                    $contact->phone_number,
                    $contact->email,
                    $contact->opt_in_status,
                    $contact->language,
                    $contact->category?->name ?? '',
                    $contact->tags->pluck('name')->implode(', '),
                ];

                foreach ($customFields as $field) {
                    $row[] = $contact->custom_attributes[$field->key] ?? '';
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        audit('contact.exported', 'Exported '.$contacts->count().' contacts to CSV.');

        return response()->streamDownload($callback, 'contacts_export_'.now()->format('Y-m-d_H-i').'.csv');
    }

    public function downloadSampleCsv()
    {
        $defaultCountryCode = get_setting('default_country_code', '+91');

        $headers = ['Name', 'Phone', 'Email', 'Tags'];
        $customFields = \App\Models\ContactField::where('team_id', Auth::user()->currentTeam->id)->get();

        foreach ($customFields as $field) {
            $headers[] = $field->key;
        }

        $callback = function () use ($headers, $defaultCountryCode) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            // Add sample rows with country code
            $row = ['John Doe', $defaultCountryCode.'1234567890', 'john@example.com', 'VIP,Lead'];
            fputcsv($file, $row);

            $row2 = ['Jane Smith', $defaultCountryCode.'9876543210', 'jane@example.com', 'Customer'];
            fputcsv($file, $row2);

            fclose($file);
        };

        return response()->streamDownload($callback, 'sample_contacts.csv');
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->countryCode = get_setting('default_country_code', '+91');
        $this->phoneNumberWithoutCode = '';
        $this->email = '';
        $this->language = 'en';
        $this->opt_in_status = 'opted_in';
        $this->category_id = null;
        $this->selectedTags = [];
        $this->customAttributes = [];
        $this->contactId = null;
    }
}
