<?php

namespace App\Livewire\Admin\Contact;

use App\Mail\NewAssignedMail;
use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\Source;
use App\Models\Status;
use App\Models\User;
use App\Rules\PurifiedInput;
use App\Traits\SendMailTrait;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ContactCreator extends Component
{
    use SendMailTrait;

    public Contact $contact;

    public string $notes_description = '';

    public array $notes = [];

    public $id;

    public $noteId;

    public $confirmingDeletion = false;

    public ?int $contactId = null;

    public int $initialNotesCount;

    public $tab = 'contact-details';

    public ?string $notetab = null;

    public function mount()
    {
        if (! checkPermission(['contact.create', 'contact.edit'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->id        = $this->getId();
        $this->contact   = $this->contactId ? Contact::findOrFail($this->contactId) : new Contact;
        $this->contactId = $contactId ?? request()->route('contactId');
        $this->notetab   = request()->query('notetab');
        $this->loadNotes();
        $this->initialNotesCount = $this->contact->notes()->count();
        if ($this->notetab === 'notes') {
            $this->tab = 'notes';
        }
    }

    protected function rules()
    {
        return [
            'contact.firstname'        => ['required', 'string', new PurifiedInput(t('sql_injection_error')), 'max:191'],
            'contact.lastname'         => ['required', 'string', new PurifiedInput(t('sql_injection_error')), 'max:191'],
            'contact.company'          => ['nullable', 'string', new PurifiedInput(t('sql_injection_error')), 'max:191'],
            'contact.type'             => ['required', 'in:customer,lead'],
            'contact.description'      => ['nullable', 'string', new PurifiedInput(t('sql_injection_error')), 'max:65535'],
            'contact.country_id'       => ['nullable', 'integer'],
            'contact.zip'              => ['nullable', 'string', new PurifiedInput(t('sql_injection_error')), 'max:15'],
            'contact.city'             => ['nullable', 'string', new PurifiedInput(t('sql_injection_error')), 'max:100'],
            'contact.state'            => ['nullable', 'string', new PurifiedInput(t('sql_injection_error')), 'max:100'],
            'contact.address'          => ['nullable', 'string', new PurifiedInput(t('sql_injection_error')), 'max:500'],
            'contact.assigned_id'      => ['nullable'],
            'contact.status_id'        => ['required', 'exists:statuses,id'],
            'contact.source_id'        => ['required', 'exists:sources,id'],
            'contact.email'            => ['nullable', 'email', 'unique:contacts,email,' . $this->contact->id, 'max:191'],
            'contact.website'          => ['nullable', 'url', new PurifiedInput(t('sql_injection_error')), 'max:100'],
            'contact.phone'            => ['required', 'unique:contacts,phone,' . $this->contact->id, new PurifiedInput(t('sql_injection_error'))],
            'contact.default_language' => ['nullable'],
        ];
    }

    public function loadNotes()
    {
        $this->notes = $this->contact->notes()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($note) => [
                'id'                => $note->id,
                'notes_description' => $note->notes_description,
                'created_at'        => $note->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function validateNotesDescription()
    {
        $this->validate([
            'notes_description' => ['nullable', 'string', new PurifiedInput(t('sql_injection_error'))],
        ]);
    }

    public function addNote()
    {
        $this->validate([
            'notes_description' => ['required', 'string', new PurifiedInput(t('sql_injection_error'))],
        ]);

        ContactNote::create([
            'notes_description' => $this->notes_description,
            'contact_id'        => $this->contact->id,
        ]);

        $this->notes_description = '';
        $this->notify([
            'type'    => 'success',
            'message' => t('note_added_successfully'),
        ]);

        $this->loadNotes();
    }

    public function confirmDelete($noteId)
    {
        $this->noteId = $noteId;

        $this->confirmingDeletion = true;
    }

    public function removeNote()
    {
        try {
            $deleted = ContactNote::where('id', $this->noteId)->delete();

            if ($deleted === 0) {
                app_log('Attempted to delete a non-existing note.', 'warning', null, [
                    'note_id' => $this->noteId,
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ]);
            }

            $this->confirmingDeletion = false;
            $this->notify([
                'type'    => 'success',
                'message' => t('note_delete_successfully'),
            ]);

            $this->loadNotes();
        } catch (\Throwable $e) {

            app_log('Failed to delete note: ' . $e->getMessage(), 'error', $e, [
                'note_id' => $this->noteId ?? 'unknown',
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            $this->notify([
                'type'    => 'danger',
                'message' => t('error_deleting_note'),
            ]);
        }
    }

    public function save()
    {
        if (checkPermission(['contact.create', 'contact.edit'])) {
            $this->validate();
            try {

                $this->contact->assigned_id = $this->contact->assigned_id ?: null;

                if (! is_null($this->contact->assigned_id) && ($this->contact->isDirty('assigned_id') || is_null($this->contact->getOriginal('assigned_id')))) {
                    $this->contact->dateassigned = now();
                }

                $this->contact->addedfrom = Auth::id();

                if (! $this->contact->exists) {
                    $this->contact->is_enabled = true;
                }

                $notesChanged = ContactNote::where('contact_id', $this->contact->id)->exists() !== ($this->initialNotesCount > 0);

                $isNewContact    = ! $this->contact->exists;
                $assignedChanged = $this->contact->isDirty('assigned_id') && ! is_null($this->contact->assigned_id);

                if ($this->contact->isDirty() || $notesChanged) {
                    $this->contact->save();

                    if (($isNewContact || $assignedChanged) && can_send_email('new-contact-assigned') && isSmtpValid()) {
                        $template = mailTemplate('new-contact-assigned');

                        if ($template->is_active) {
                            $assignee = User::select('email', 'firstname', 'lastname')->find($this->contact->assigned_id);

                            if ($assignee) {
                                $assignedEmail = $assignee->email;
                                $assignedName  = "{$assignee->firstname} {$assignee->lastname}";

                                $this->sendMail($assignedEmail, new NewAssignedMail(
                                    "Hello, {$assignedName}",
                                    $template->slug,
                                    Auth::user()->id,
                                    $this->contact->id
                                ));
                            }
                        }
                    }

                    $this->initialNotesCount = ContactNote::where('contact_id', $this->contact->id)->count();

                    $this->notify([
                        'type'    => 'success',
                        'message' => $this->contact->wasRecentlyCreated
                            ? t('contact_created_successfully')
                            : t('contact_update_successfully'),
                    ], true);
                }

                return $this->redirect(route('admin.contacts.list'));
            } catch (\Throwable $e) {

                app_log('Error while saving contact: ' . $e->getMessage(), 'error', $e, [
                    'contact_id' => $this->contact->id ?? 'unknown',
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                ]);

                $this->notify([
                    'type'    => 'danger',
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->redirect(route('admin.contacts.list'), navigate: true);
    }

    public function render()
    {
        $data['statuses']  = Status::all();
        $data['sources']   = Source::all();
        $data['countries'] = getCountryList();
        $data['users']     = User::all();

        return view('livewire.admin.contact.contact-creator', $data);
    }
}
