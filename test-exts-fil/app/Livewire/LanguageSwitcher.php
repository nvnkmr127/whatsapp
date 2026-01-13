<?php

namespace App\Livewire;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public $currentLocale;

    public function mount()
    {
        $this->currentLocale = Session::get('locale', config('app.locale'));
    }

    public function setLocale($lang)
    {
        $locale = Session::get('locale', config('app.locale'));
        Cache::forget("translations.{$locale}");
        Session::put('locale', $lang);
        App::setLocale($lang);
        $this->currentLocale = $lang;

        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
