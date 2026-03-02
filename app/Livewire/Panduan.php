<?php

namespace App\Livewire;

use Livewire\Component;

class Panduan extends Component
{
    public function render(): \Illuminate\View\View
    {
        $layout = auth()->user()?->hasRole('admin') ? 'layouts.admin' : 'layouts.ops';

        return view('livewire.panduan')->layout($layout, ['title' => 'Panduan']);
    }
}
