<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use App\Models\ContactTag;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ContactManager extends Component
{

    use WithPagination;
    use \Livewire\WithFileUploads;

    public $search = '';
    public $filterTag = '';
    public $filterStatus = '';

    // Modal state
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $contactId;

    // Form fields
    public $name;
    public $phone_number;
    public $email;
    public $language = 'en';
    public $opt_in_status = 'opted_in';
    public $category_id;
    public $selectedTags = [];
    public $customAttributes = []; // For holding dynamic field values

    // Import State
    public $isImportModalOpen = false;
    public $importFile;
    public $csvHeaders = [];
    public $columnMapping = []; // csv_header => system_field
    public $importResult = null;

    // Custom Fields State
    public $isFieldModalOpen = false;
    public $fieldId;
    public $fieldLabel;
    public $fieldType = 'text';
    public $fieldOptions = ''; // Comma separated for editing

    protected $queryString = [
        'search' => ['except' => ''],
        'filterTag' => ['except' => ''],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'phone_number' => 'required|string|max:20', // Add more specific validation if needed
        'email' => 'nullable|email|max:255',
        'language' => 'required|string|max:10',
        'opt_in_status' => 'required|in:opted_in,opted_out',
        'category_id' => 'nullable|exists:categories,id',
        'selectedTags' => 'array',
        'customAttributes' => 'array',
    ];

    #[Layout('components.layouts.app')]
    public function render()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-contacts');
        $query = Contact::where('team_id', Auth::user()->currentTeam->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone_number', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterTag) {
            $query->whereHas('tags', function ($q) {
                $q->where('id', $this->filterTag);
            });
        }

        if ($this->filterStatus) {
            $query->where('opt_in_status', $this->filterStatus);
        }

        $contacts = $query->with(['tags', 'category'])->latest()->paginate(15);
        $tags = ContactTag::where('team_id', Auth::user()->currentTeam->id)->get();
        $customFields = \App\Models\ContactField::where('team_id', Auth::user()->currentTeam->id)->get();
        $categories = \App\Models\Category::where('team_id', Auth::user()->currentTeam->id)
            ->whereIn('target_module', ['all', 'contacts'])
            ->get();

        return view('livewire.contacts.contact-manager', compact('contacts', 'tags', 'customFields', 'categories'));
    }

    public function create()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-contacts');
        $this->resetInputFields();
        $this->openModal();
    }

    // View Modal State
    public $isViewModalOpen = false;
    public $viewingContact = null;

    public function viewContact($id)
    {
        $this->viewingContact = Contact::with('tags')->where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    public function getConversationRoute($contactId)
    {
        $contact = Contact::where('team_id', Auth::user()->currentTeam->id)->find($contactId);
        if (!$contact)
            return route('chat');

        $conversationId = $contact->conversations->first()?->id;

        return $conversationId
            ? route('chat', ['activeConversationId' => $conversationId])
            : route('chat');
    }

    public function closeViewModal()
    {
        $this->isViewModalOpen = false;
        $this->viewingContact = null;
    }

    public function edit($id)
    {
        // Close view modal if open
        $this->isViewModalOpen = false;
        $this->viewingContact = null;

        $contact = Contact::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->contactId = $id;
        $this->name = $contact->name;
        $this->phone_number = $contact->phone_number;
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

        $data = [
            'team_id' => Auth::user()->currentTeam->id,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'language' => $this->language,
            'opt_in_status' => $this->opt_in_status,
            'category_id' => $this->category_id ?: null,
            'custom_attributes' => $this->customAttributes,
        ];

        if ($this->contactId) {
            $data['id'] = $this->contactId;
        }

        $contactService = new \App\Services\ContactService();
        $contact = $contactService->createOrUpdate($data);

        $contact->tags()->sync($this->selectedTags);

        audit(
            $this->contactId ? 'contact.updated' : 'contact.created',
            ($this->contactId ? "Updated" : "Created") . " contact '{$contact->name}' ({$contact->phone_number})",
            $contact
        );

        session()->flash('message', $this->contactId ? 'Contact updated successfully.' : 'Contact created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function confirmDelete($id)
    {
        $this->contactId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if ($this->contactId) {
            $contact = Contact::where('team_id', Auth::user()->currentTeam->id)->find($this->contactId);
            if ($contact) {
                $contact->delete();
                audit('contact.deleted', "Deleted contact '{$contact->name}' ({$contact->phone_number})", $contact);
                session()->flash('message', 'Contact deleted successfully.');
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

    // Tag Management
    public $isTagModalOpen = false;
    public $newTagName = '';
    public $newTagColor = '#10B981'; // Default Green

    public function openTagModal()
    {
        $this->isTagModalOpen = true;
        $this->newTagName = '';
        $this->newTagColor = '#10B981';
    }

    public function closeTagModal()
    {
        $this->isTagModalOpen = false;
    }

    public function createTag()
    {
        $this->validate([
            'newTagName' => 'required|string|max:50',
            'newTagColor' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        ContactTag::create([
            'team_id' => Auth::user()->currentTeam->id,
            'name' => $this->newTagName,
            'color' => $this->newTagColor,
        ]);

        $this->newTagName = '';
        $this->newTagColor = '#10B981'; // Reset
        session()->flash('tag_message', 'Tag created successfully.');
    }

    public function deleteTag($id)
    {
        $tag = ContactTag::where('team_id', Auth::user()->currentTeam->id)->find($id);
        if ($tag) {
            $tag->delete();
            session()->flash('tag_message', 'Tag deleted successfully.');
        }
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

    public function closeFieldModal()
    {
        $this->isFieldModalOpen = false;
        $this->resetFieldInput();
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
        $this->importFile = null;
        $this->csvHeaders = [];
        $this->columnMapping = [];
    }

    public function closeImportModal()
    {
        $this->isImportModalOpen = false;
    }

    public function updatedImportFile()
    {
        $this->validate([
            'importFile' => 'required|mimes:csv,txt|max:10240',
        ]);

        $csv = \League\Csv\Reader::createFromPath($this->importFile->getRealPath(), 'r');
        $csv->setHeaderOffset(0);
        $this->csvHeaders = $csv->getHeader();

        // Auto-map common fields
        foreach ($this->csvHeaders as $header) {
            $lowerHeader = strtolower($header);
            if (in_array($lowerHeader, ['name', 'phone', 'phone_number', 'email', 'tags'])) {
                $target = $lowerHeader === 'phone' ? 'phone_number' : $lowerHeader;
                $this->columnMapping[$header] = $target;
            }
        }
    }

    public function importContacts()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-contacts');
        $this->validate([
            'importFile' => 'required',
            'columnMapping' => 'required|array',
        ]);

        $importService = new \App\Services\ContactImportService(Auth::user()->currentTeam);
        $result = $importService->import($this->importFile->getRealPath(), $this->columnMapping);

        $this->importResult = $result;

        if ($result['success_count'] > 0) {
            audit('contact.imported', "Imported {$result['success_count']} contacts from CSV.", null, ['result' => $result]);
            session()->flash('import_message', "Imported {$result['success_count']} contacts successfully.");
        }
    }

    public function downloadSampleCsv()
    {
        $headers = ['Name', 'Phone', 'Email', 'Tags'];
        $customFields = \App\Models\ContactField::where('team_id', Auth::user()->currentTeam->id)->get();

        foreach ($customFields as $field) {
            $headers[] = $field->key;
        }

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            // Add a sample row
            $row = ['John Doe', '1234567890', 'john@example.com', 'VIP,Lead'];
            // Fill custom fields with blanks or sample data if needed
            // For now, leave custom fields blank in sample to avoid confusion

            fputcsv($file, $row);
            fclose($file);
        };

        return response()->streamDownload($callback, 'sample_contacts.csv');
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->phone_number = '';
        $this->email = '';
        $this->language = 'en';
        $this->opt_in_status = 'opted_in';
        $this->category_id = null;
        $this->selectedTags = [];
        $this->customAttributes = [];
        $this->contactId = null;
    }
}
