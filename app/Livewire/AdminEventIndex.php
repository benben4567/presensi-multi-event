<?php

namespace App\Livewire;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Event')]
class AdminEventIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(string $eventId): void
    {
        $this->dispatch('show-confirm',
            message: 'Hapus event ini? Semua sesi dan data terkait akan ikut terhapus.',
            confirmedEvent: 'delete-event',
            confirmedData: ['eventId' => $eventId],
            confirmLabel: 'Hapus',
            cancelLabel: 'Batal',
        );
    }

    #[On('delete-event')]
    public function delete(string $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $event->delete();

        $this->dispatch('toast', message: 'Event berhasil dihapus.', type: 'success');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin-event-index', [
            'events' => Event::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest('start_at')
                ->paginate(15),
        ]);
    }
}
