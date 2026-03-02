<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class UiToast extends Component
{
    public string $message = '';

    public string $type = 'success';

    public bool $show = false;

    #[On('toast')]
    public function showToast(string $message, string $type = 'success'): void
    {
        $this->message = $message;
        $this->type = $type;
        $this->show = true;
    }

    public function dismiss(): void
    {
        $this->show = false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.ui-toast');
    }
}
