<?php

namespace App\Livewire\Admin\Contact;

use App\Models\Contact;
use Livewire\Component;

class ContactList extends Component
{
    public Contact $contact;

    public ?int $contactId = null;

    public $confirmingDeletion = false;

    public $viewContactModal = false;

    public $contact_id = null;

    public array $selectedStatus = [];

    public $notes = [];

    public bool $isBulckDelete = false;

    protected $listeners = [
        'editContact'   => 'editContact',
        'confirmDelete' => 'confirmDelete',
        'viewContact'   => 'viewContact',
    ];

    public function mount()
    {
        if (! checkPermission('contact.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
    }

    public function createContact()
    {
        $this->redirect(route('admin.contacts.save'));
    }

    public function viewContact($contactId)
    {
        $this->contact               = Contact::with('notes')->findOrFail($contactId);
        $country                     = collect(getCountryList())->firstWhere('id', (string) $this->contact->country_id);
        $this->contact->country_name = $country['short_name'] ?? null;
        $this->notes                 = $this->contact->notes()->latest()->get();
        $this->viewContactModal      = true;
    }

    public function importContact()
    {
        $this->redirect(route('admin.contacts.imports'));
    }

    public function editContact($contactId)
    {
        $this->contact = Contact::findOrFail($contactId);
        $this->redirect(route('admin.contacts.save', ['contactId' => $contactId]));
    }

    public function updatedConfirmingDeletion($value)
    {
        if (! $value) {
            $this->js('window.pgBulkActions.clearAll()');
        }
    }

    public function confirmDelete($contactId)
    {
        $this->contact_id = $contactId;

        $this->isBulckDelete = is_array($this->contact_id) && count($this->contact_id) !== 1 ? true : false;

        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        try {
            if (is_array($this->contact_id) && count($this->contact_id) !== 0) {
                $selectedIds = $this->contact_id;
                dispatch(function () use ($selectedIds) {
                    Contact::whereIn('id', $selectedIds)
                        ->chunk(100, function ($contacts) {
                            foreach ($contacts as $contact) {
                                $contact->delete();
                            }
                        });
                })->afterResponse();
                $this->contact_id = null;
                $this->js('window.pgBulkActions.clearAll()');
                $this->notify([
                    'type'    => 'success',
                    'message' => t('contacts_delete_successfully'),
                ]);
            } else {

                $contact          = Contact::findOrFail($this->contact_id);
                $this->contact_id = null;
                $contact->delete();

                $this->notify([
                    'type'    => 'success',
                    'message' => t('contact_delete_success'),
                ]);
            }

            $this->confirmingDeletion = false;
            $this->dispatch('pg:eventRefresh-contact-table-tiybqj-table');
        } catch (\Exception $e) {

            $this->notify([
                'type'    => 'danger',
                'message' => t('an_error_occured_deleting_contact'),
            ]);
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-contact-table-tiybqj-table');
    }

    public function render()
    {
        return view('livewire.admin.contact.contact-list');
    }
}
