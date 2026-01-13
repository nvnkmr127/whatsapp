<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Toggle extends Component
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
    ) {}

    public function render()
    {
        return view('components.toggle');
    }
}
