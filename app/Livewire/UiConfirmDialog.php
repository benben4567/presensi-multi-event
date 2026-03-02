<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class UiConfirmDialog extends Component
{
    public string $message = '';

    public string $confirmLabel = 'Ya, lanjutkan';

    public string $cancelLabel = 'Batal';

    public bool $show = false;

    /** Nama event yang di-dispatch ke komponen pemanggil saat dikonfirmasi. */
    public string $confirmedEvent = '';

    /** Data tambahan yang disertakan bersama event konfirmasi. */
    public array $confirmedData = [];

    #[On('show-confirm')]
    public function open(
        string $message,
        string $confirmedEvent,
        array $confirmedData = [],
        string $confirmLabel = 'Ya, lanjutkan',
        string $cancelLabel = 'Batal',
    ): void {
        $this->message = $message;
        $this->confirmedEvent = $confirmedEvent;
        $this->confirmedData = $confirmedData;
        $this->confirmLabel = $confirmLabel;
        $this->cancelLabel = $cancelLabel;
        $this->show = true;
    }

    public function confirm(): void
    {
        $this->show = false;
        $this->dispatch($this->confirmedEvent, ...$this->confirmedData);
    }

    public function cancel(): void
    {
        $this->show = false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.ui-confirm-dialog');
    }
}
