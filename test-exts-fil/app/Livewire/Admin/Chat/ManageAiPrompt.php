<?php

namespace App\Livewire\Admin\Chat;

use App\Models\AiPrompt;
use App\Rules\PurifiedInput;
use Livewire\Component;
use Livewire\WithPagination;

class ManageAiPrompt extends Component
{
    use WithPagination;

    public AiPrompt $prompt;

    public $showAiPromptModal = false;

    public $confirmingDeletion = false;

    public $prompt_id = null;

    protected $listeners = [
        'editAiPrompt'  => 'editAiPrompt',
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission(['ai_prompt.view', 'ai_prompt.create', 'ai_prompt.edit', 'ai_prompt.delete'])) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->resetForm();
        $this->prompt = new AiPrompt;
    }

    public function validatePromtAction()
    {
        $this->validate([
            'prompt.action' => ['required', 'string', 'max:255', new PurifiedInput(t('sql_injection_error'))],
        ]);
    }

    protected function rules()
    {
        return [
            'prompt.name' => [
                'required',
                'max:255',
                'unique:ai_prompts,name,' . ($this->prompt->id ?? 'NULL'),
                new PurifiedInput(t('sql_injection_error')),
            ],
            'prompt.action' => ['required', new PurifiedInput(t('sql_injection_error'))],
        ];
    }

    public function createAiPrompt()
    {
        $this->resetForm();
        $this->showAiPromptModal = true;
    }

    public function save()
    {
        if (checkPermission(['ai_prompt.create', 'ai_prompt.edit'])) {
            $this->validate();
            try {
                $this->prompt->save();
                $this->showAiPromptModal = false;

                $message = $this->prompt->wasRecentlyCreated
                    ? t('ai_prompt_saved_successfully')
                    : t('ai_prompt_updated_successfully');

                $this->notify(['type' => 'success', 'message' => $message]);
                $this->dispatch('pg:eventRefresh-ai-prompt-table-iywkrn-table');
            } catch (\Throwable $e) {
                whatsapp_log('Error saving AI Prompt', 'error', ['prompt' => $this->prompt->toArray()], $e);
                $this->notify(['type' => 'danger', 'message' => t('ai_prompt_save_failed')]);
            }
        }
    }

    public function editAiPrompt($promptId)
    {
        try {
            $prompt       = AiPrompt::findOrFail($promptId);
            $this->prompt = $prompt;
            $this->resetValidation();
            $this->showAiPromptModal = true;
        } catch (\Throwable $e) {
            whatsapp_log('Error editing AI Prompt', 'error', ['prompt_id' => $promptId], $e);
            $this->notify(['type' => 'danger', 'message' => t('ai_prompt_edit_failed')]);
        }
    }

    public function confirmDelete($promptId)
    {
        $this->prompt_id          = $promptId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('ai_prompt.delete')) {
            try {
                $prompt = AiPrompt::find($this->prompt_id);
                if ($prompt) {
                    $prompt->delete();
                }

                $this->confirmingDeletion = false;
                $this->reset();
                $this->prompt_id = null;
                $this->resetPage();

                $this->notify(['type' => 'success', 'message' => t('ai_prompt_delete_successfully')]);
                $this->dispatch('pg:eventRefresh-ai-prompt-table-iywkrn-table');
            } catch (\Throwable $e) {
                whatsapp_log('Error deleting AI Prompt', 'error', ['prompt_id' => $this->prompt_id], $e);
                $this->notify(['type' => 'danger', 'message' => t('ai_prompt_delete_failed')]);
            }
        }
    }

    private function resetForm()
    {
        $this->resetExcept('prompt');
        $this->resetValidation();
        $this->prompt = new AiPrompt;
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-ai-prompt-table-iywkrn-table');
    }

    public function render()
    {
        return view('livewire.admin.chat.manage-ai-prompt');
    }
}
