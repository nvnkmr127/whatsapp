<?php

namespace App\Livewire\Admin\Contact;

use App\Models\Source;
use App\Rules\PurifiedInput;
use Livewire\Component;
use Livewire\WithPagination;

class ManageSource extends Component
{
    use WithPagination;

    public Source $source;

    public $showSourceModal = false;

    public $confirmingDeletion = false;

    public $source_id = null;

    protected $listeners = [
        'editSource'    => 'editSource',
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission('source.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->resetForm();
        $this->source = new Source;
    }

    protected function rules()
    {
        return [
            'source.name' => [
                'required',
                'unique:sources,name,' . ($this->source->id ?? 'NULL'),
                new PurifiedInput(t('sql_injection_error')),
                'max:150',
            ],
        ];
    }

    public function createSourcePage()
    {
        $this->resetForm();
        $this->showSourceModal = true;
    }

    public function save()
    {
        if (checkPermission(['source.create', 'source.edit'])) {
            $this->validate();

            try {
                if ($this->source->isDirty()) {
                    $this->source->save();
                    $this->showSourceModal = false;

                    $message = $this->source->wasRecentlyCreated
                    ? t('source_saved_successfully')
                    : t('source_update_successfully');

                    $this->notify(['type' => 'success', 'message' => $message]);
                    $this->dispatch('pg:eventRefresh-source-table-9hsleg-table');
                } else {
                    $this->showSourceModal = false;
                }
            } catch (\Exception $e) {
                app_log('Source save failed: ' . $e->getMessage(), 'error', $e, [
                    'source_id'  => $this->source->id ?? null,
                    'attributes' => $this->source->getAttributes(),
                    'dirty'      => $this->source->getDirty(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                ]);

                $this->notify(['type' => 'danger', 'message' => t('source_save_failed')]);
            }
        }
    }

    public function editSource($sourceId)
    {
        $source       = Source::findOrFail($sourceId);
        $this->source = $source;
        $this->resetValidation();
        $this->showSourceModal = true;
    }

    public function confirmDelete($sourceId)
    {
        $this->source_id          = $sourceId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('source.delete')) {
            try {
                $source = Source::find($this->source_id);

                if ($source) {
                    $source->delete();
                }

                $this->confirmingDeletion = false;
                $this->resetForm();
                $this->source_id = null;
                $this->resetPage();

                $this->notify(['type' => 'success', 'message' => t('source_delete_successfully')]);
                $this->dispatch('pg:eventRefresh-source-table-9hsleg-table');
            } catch (\Exception $e) {
                app_log('Source deletion failed: ' . $e->getMessage(), 'error', $e, [
                    'source_id' => $this->source_id,
                ]);

                $this->notify(['type' => 'danger', 'message' => t('source_delete_failed')]);
            }
        }
    }

    private function resetForm()
    {
        $this->resetExcept('source');
        $this->resetValidation();
        $this->source = new Source;
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-source-table-9hsleg-table');
    }

    public function render()
    {
        return view('livewire.admin.contact.manage-source');
    }
}
