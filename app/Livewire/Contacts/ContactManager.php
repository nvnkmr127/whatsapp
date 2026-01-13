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
    public $opt_in_status = 'OPT_IN';
    public $selectedTags = [];

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
        'selectedTags' => 'array',
    ];

    #[Layout('components.layouts.app')]
    public function render()
    {
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

        $contacts = $query->with('tags')->latest()->paginate(15);
        $tags = ContactTag::where('team_id', Auth::user()->currentTeam->id)->get();

        return view('livewire.contacts.contact-manager', compact('contacts', 'tags'));
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function edit($id)
    {
        $contact = Contact::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->contactId = $id;
        $this->name = $contact->name;
        $this->phone_number = $contact->phone_number;
        $this->email = $contact->email;
        $this->language = $contact->language;
        $this->opt_in_status = $contact->opt_in_status;
        $this->selectedTags = $contact->tags->pluck('id')->toArray();
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        $contact = Contact::updateOrCreate(
            ['id' => $this->contactId],
            [
                'team_id' => Auth::user()->currentTeam->id,
                'name' => $this->name,
                'phone_number' => $this->phone_number,
                'email' => $this->email,
                'language' => $this->language,
                'opt_in_status' => $this->opt_in_status,
            ]
        );

        $contact->tags()->sync($this->selectedTags);

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

    private function resetInputFields()
    {
        $this->name = '';
        $this->phone_number = '';
        $this->email = '';
        $this->language = 'en';
        $this->opt_in_status = 'opted_in';
        $this->selectedTags = [];
        $this->contactId = null;
    }
}
