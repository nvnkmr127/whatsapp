<?php

namespace App\Livewire\Admin\Contact;

use App\Models\Status;
use App\Rules\PurifiedInput;
use Livewire\Component;
use Livewire\WithPagination;

class ManageStatus extends Component
{
    use WithPagination;

    public Status $status;

    public $showStatusModal = false;

    public $status_id = null;

    public $confirmingDeletion = false;

    protected $listeners = [
        'editStatus'    => 'editStatus',
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission('status.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->resetForm();
        $this->status = new Status;
    }

    protected function rules()
    {
        return [
            'status.name' => [
                'required',
                'min:3',
                'max:255',
                'unique:statuses,name,' . ($this->status->id ?? 'NULL'),
                new PurifiedInput(t('sql_injection_error')),
            ],
            'status.color' => [
                'required',
                'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
                new PurifiedInput(t('sql_injection_error')),
            ],
        ];
    }

    public function createStatusPage()
    {
        $this->resetForm();
        $this->showStatusModal = true;
    }

    public function save()
    {
        if (checkPermission(['status.create', 'status.edit'])) {
            $this->validate();
            try {
                if ($this->status->isDirty()) {
                    $this->status->save();
                    $this->status->isdefault = 0;
                    $this->showStatusModal   = false;

                    $message = $this->status->wasRecentlyCreated
                    ? t('status_save_successfully')
                    : t('status_update_successfully');

                    $this->notify(['type' => 'success', 'message' => $message]);
                    $this->dispatch('pg:eventRefresh-status-table-nz8nvq-table');
                } else {
                    $this->showStatusModal = false;
                }
            } catch (\Exception $e) {
                app_log('Status save failed: ' . $e->getMessage(), 'error', $e, [
                    'status_id' => $this->status->id ?? null,
                ]);

                $this->notify(['type' => 'danger', 'message' => t('status_save_failed')]);
            }
        }
    }

    public function editStatus($statusId)
    {
        $status       = Status::findOrFail($statusId);
        $this->status = $status;
        $this->resetValidation();
        $this->showStatusModal = true;
    }

    public function confirmDelete($statusId)
    {
        $this->status_id          = $statusId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('status.delete')) {
            try {
                $status = Status::find($this->status_id);

                if ($status) {
                    $status->delete();
                }

                $this->confirmingDeletion = false;
                $this->resetForm();
                $this->status_id = null;
                $this->resetPage();

                $this->notify(['type' => 'success', 'message' => t('status_delete_successfully')]);
                $this->dispatch('pg:eventRefresh-status-table-nz8nvq-table');
            } catch (\Exception $e) {
                app_log('Status deletion failed: ' . $e->getMessage(), 'error', $e, [
                    'status_id' => $this->status_id,
                ]);

                $this->notify(['type' => 'danger', 'message' => t('status_delete_failed')]);
            }
        }
    }

    private function resetForm()
    {
        $this->resetExcept('status');
        $this->resetValidation();
        $this->status = new Status;
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-status-table-nz8nvq-table');
    }

    public function render()
    {
        return view('livewire.admin.contact.manage-status');
    }
}
