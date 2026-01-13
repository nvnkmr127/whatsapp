<?php

namespace App\Livewire\Admin\Settings\Language;

use Livewire\Component;
use Livewire\WithPagination;

class TranslationManager extends Component
{
    use WithPagination;

    public $languageCode;

    public function mount()
    {
        $code = request()->route('code');
        if ($code === 'en') {
            return to_route('admin.languages');
        }
        $this->languageCode = $code;
    }

    public function render()
    {
        return view('livewire.admin.settings.language.translation-manager');
    }
}
