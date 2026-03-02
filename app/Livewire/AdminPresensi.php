<?php

namespace App\Livewire;

use App\Models\AttendanceLog;
use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Presensi')]
class AdminPresensi extends Component
{
    use WithPagination;

    public ?string $eventId = null;

    public ?int $sessionId = null;

    public function updatedEventId(): void
    {
        $this->sessionId = null;
        $this->resetPage();
    }

    public function updatedSessionId(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $events = Event::orderByDesc('start_at')->get(['id', 'name']);

        $sessions = $this->eventId
            ? Event::find($this->eventId)?->sessions()->orderBy('start_at')->get(['id', 'name'])
            : collect();

        $logs = AttendanceLog::query()
            ->with(['eventParticipant.participant', 'session', 'operator'])
            ->when($this->eventId, fn ($q) => $q->where('event_id', $this->eventId))
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))
            ->orderByDesc('scanned_at')
            ->paginate(25);

        return view('livewire.admin-presensi', compact('events', 'sessions', 'logs'));
    }
}
