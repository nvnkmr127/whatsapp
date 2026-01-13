<?php

namespace App\Livewire\Admin\Chat;

use App\Models\CannedReply;
use App\Rules\PurifiedInput;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ManageCannedReply extends Component
{
    use WithPagination;

    public CannedReply $canned;

    public $showCannedModal = false;

    public $confirmingDeletion = false;

    public $canned_id = null;

    protected $listeners = [
        'editCannedPage' => 'editCannedPage',
        'confirmDelete'  => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission(['canned_reply.view', 'canned_reply.edit', 'canned_reply.create', 'canned_reply.delete'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->resetForm();
        $this->canned = new CannedReply;
    }

    public function validateCannedDescription()
    {
        $this->validate([
            'canned.description' => ['required', 'string', new PurifiedInput(t('sql_injection_error'))],
        ]);
    }

    protected function rules()
    {
        return [
            'canned.title' => [
                'required',
                'min:3',
                'max:255',
                'unique:canned_replies,title,' . ($this->canned->id ?? 'NULL'),
                new PurifiedInput(t('sql_injection_error')),
            ],
            'canned.description' => ['required', new PurifiedInput(t('sql_injection_error'))],
        ];
    }

    public function createCanned()
    {
        if (! $this->showCannedModal) {
            $this->resetForm();
            $this->showCannedModal = true;
        }
    }

    public function save()
    {
        if (checkPermission(['canned_reply.create', 'canned_reply.edit'])) {
            $this->validate();
            try {
                if (! $this->canned->exists) {
                    $this->canned->is_public  = 0;
                    $this->canned->added_from = Auth::id();
                }

                $this->canned->save();

                $this->showCannedModal = false;

                $message = $this->canned->wasRecentlyCreated
                    ? t('canned_reply_save_successfully')
                    : t('canned_reply_update_successfully');

                $this->notify(['type' => 'success', 'message' => $message]);
                $this->dispatch('pg:eventRefresh-canned-reply-table-qxiqed-table');
            } catch (\Throwable $e) {
                whatsapp_log('Error saving Canned Reply', 'error', ['canned' => $this->canned->toArray()], $e);
                $this->notify(['type' => 'danger', 'message' => t('canned_reply_save_failed')]);
            }
        }
    }

    public function editCannedPage($cannedId)
    {
        try {
            $canned       = CannedReply::findOrFail($cannedId);
            $this->canned = $canned;
            $this->resetValidation();
            $this->showCannedModal = true;
        } catch (\Throwable $e) {
            whatsapp_log('Error editing Canned Reply', 'error', ['canned_id' => $cannedId], $e);
            $this->notify(['type' => 'danger', 'message' => t('canned_reply_edit_failed')]);
        }
    }

    public function confirmDelete($cannedId)
    {
        $this->canned_id          = $cannedId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('canned_reply.delete')) {
            try {
                $cannedReply = CannedReply::find($this->canned_id);
                if ($cannedReply) {
                    $cannedReply->delete();
                }

                $this->confirmingDeletion = false;
                $this->resetForm();
                $this->canned_id = null;
                $this->resetPage();

                $this->notify(['type' => 'success', 'message' => t('canned_reply_delete_successfully')]);
                $this->dispatch('pg:eventRefresh-canned-reply-table-qxiqed-table');
            } catch (\Throwable $e) {
                whatsapp_log('Error deleting Canned Reply', 'error', ['canned_id' => $this->canned_id], $e);
                $this->notify(['type' => 'danger', 'message' => t('canned_reply_delete_failed')]);
            }
        }
    }

    private function resetForm()
    {
        $this->resetExcept('canned');
        $this->resetValidation();

        $this->canned = new CannedReply;
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-canned-reply-table-qxiqed-table');
    }

    public function render()
    {
        return view('livewire.admin.chat.manage-canned-reply');
    }
}
