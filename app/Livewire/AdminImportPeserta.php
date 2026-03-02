<?php

namespace App\Livewire;

use App\Actions\ImportPesertaAction;
use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Impor Peserta')]
class AdminImportPeserta extends Component
{
    use WithFileUploads;

    public string $eventId;

    #[Validate(['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'])]
    public mixed $file = null;

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public function mount(Event $event): void
    {
        $this->eventId = $event->id;
    }

    public function import(ImportPesertaAction $action): void
    {
        $this->validate();

        $event = Event::findOrFail($this->eventId);
        $path = $this->file->getRealPath();

        $this->result = $action->execute($event, $path);
        $this->file = null;

        $this->dispatch('toast',
            message: "Impor selesai: {$this->result['imported']} ditambahkan, {$this->result['skipped']} dilewati, {$this->result['errors']} error.",
            type: $this->result['errors'] > 0 ? 'warning' : 'success',
        );
    }

    public function render(): \Illuminate\View\View
    {
        $event = Event::findOrFail($this->eventId);

        return view('livewire.admin-import-peserta', compact('event'));
    }
}
