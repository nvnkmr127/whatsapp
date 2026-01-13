<?php

namespace App\Livewire\Admin\Settings\Language;

use App\Models\Language;
use App\Rules\PurifiedInput;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class LanguageManager extends Component
{
    public Language $language;

    public $showLanguageModal = false;

    public $confirmingDeletion = false;

    public $language_id = null;

    protected $listeners = [
        'editLanguage'  => 'editLanguage',
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! auth()->user()->is_admin == 1) {
            return redirect(route('admin.dashboard'));
        }
        $this->resetForm();
        $this->language = new Language;
    }

    protected function rules()
    {
        return [
            'language.name' => [
                'required',
                'string',
                'max:255',
                'unique:languages,name,' . ($this->language->id ?? 'NULL'),
                new PurifiedInput(t('sql_injection_error')),
            ],
            'language.code' => [
                'required',
                'string',
                'min:2',
                'max:3',
                'unique:languages,code,' . ($this->language->id ?? 'NULL'),
                'regex:/^[a-zA-Z]+$/',
                new PurifiedInput(t('sql_injection_error')),
            ],
        ];
    }

    public function createLanguage()
    {
        $this->resetForm();
        $this->showLanguageModal = true;
    }

    private function resetForm()
    {
        $this->reset();
        $this->resetValidation();
        $this->language = new Language;
    }

    public function confirmDelete($languageId)
    {
        $this->language_id        = $languageId;
        $this->confirmingDeletion = true;
    }

    public function editLanguage($languageCode)
    {
        if ($languageCode === 'en') {
            return $this->notify(['type' => 'danger', 'message' => t('edit_english_language_not_allowed')]);
        }

        $language       = Language::where('code', $languageCode)->firstOrFail();
        $this->language = $language;
        $this->resetValidation();
        $this->showLanguageModal = true;
    }

    public function save()
    {
        $this->validate();

        try {

            $isUpdate = isset($this->language->id);

            if (! $this->language->isDirty()) {
                $this->showLanguageModal = false;

                return;
            }

            // Get the old language code only if it's an update
            $originalCode = $isUpdate ? $this->language->getOriginal('code') : null;

            $newCode              = strtolower($this->language->code);
            $this->language->code = $newCode;

            // File paths
            $oldFilePath = resource_path("lang/translations/{$originalCode}.json");
            $newFilePath = resource_path("lang/translations/{$newCode}.json");

            // Handle file operations
            $isFileOperationSuccessful = false;

            if ($isUpdate) {
                // Rename the existing file if the language code has changed
                if ($originalCode !== $newCode && File::exists($oldFilePath)) {
                    File::move($oldFilePath, $newFilePath);
                }
                $isFileOperationSuccessful = true; // If file move is successful
            } else {
                // Create a new JSON file from the English template
                $sourcePath = resource_path('lang/en.json');

                if (! File::exists(dirname($newFilePath))) {
                    File::makeDirectory(dirname($newFilePath), 0755, true);
                }

                File::copy($sourcePath, $newFilePath);
                $isFileOperationSuccessful = true;
            }

            // Only proceed to save if file operation is successful
            if ($isFileOperationSuccessful) {

                $this->language->save();

                $this->notify(['type' => 'success', 'message' => $isUpdate ? t('language_update_successfully') : t('language_added_successfully')], true);

                return redirect()->route('admin.languages');
            }
        } catch (\Exception $e) {
            app_log('Language save failed: ' . $e->getMessage(), 'error', $e, [
                'language_id'   => $this->language->id ?? null,
                'new_code'      => $newCode            ?? null,
                'original_code' => $originalCode       ?? null,
                'is_update'     => $isUpdate,
            ]);

            $this->notify(['type' => 'danger', 'message' => t('language_handling_error')], true);
        }

        $this->resetForm();
        $this->showLanguageModal = false;
    }

    public function delete()
    {
        try {
            $language = Language::findOrFail($this->language_id);

            // Check if the language is default or English
            if (($language->name === 'English' && $language->code === 'en')) {
                $message = ($language->name === 'English' && $language->code === 'en') ? 'English' : 'Default';
                $this->notify(['type' => 'danger', 'message' => t('deleting_the') . $message . t('language_is_not_allowed')]);
                $this->confirmingDeletion = false;

                return;
            }

            // Delete language file if exists
            $filePath = resource_path("lang/translations/{$language->code}.json");
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            // Delete language and notify success
            $langName = $language->name;
            $language->delete();
            $this->confirmingDeletion = false;
            $this->dispatch('pg:eventRefresh-language-table-eoksxv-table');
            $this->notify(['type' => 'success', 'message' => $langName . t('language_delete_successfully')]);
        } catch (\Exception $e) {
            app_log('Language delete failed: ' . $e->getMessage(), 'error', $e, [
                'language_id' => $this->language_id,
            ]);

            $this->notify(['type' => 'danger', 'message' => t('language_delete_failed')], true);
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-language-table-eoksxv-table');
    }

    public function render()
    {
        return view('livewire.admin.settings.language.language-manager', [
            'languages' => Language::all(),
        ]);
    }
}
