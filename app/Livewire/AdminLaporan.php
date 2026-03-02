<?php

namespace App\Livewire;

use App\Enums\AttendanceAction;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\EventParticipant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Laporan')]
class AdminLaporan extends Component
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

        $rekap = collect();

        if ($this->eventId && $this->sessionId) {
            $rekap = EventParticipant::query()
                ->with('participant')
                ->where('event_id', $this->eventId)
                ->orderBy('id')
                ->paginate(25)
                ->through(function (EventParticipant $enrollment): EventParticipant {
                    $logs = AttendanceLog::where('event_participant_id', $enrollment->id)
                        ->where('session_id', $this->sessionId)
                        ->get(['action', 'scanned_at']);

                    $enrollment->checkInAt = $logs
                        ->firstWhere('action', AttendanceAction::CheckIn)
                        ?->scanned_at;

                    $enrollment->checkOutAt = $logs
                        ->firstWhere('action', AttendanceAction::CheckOut)
                        ?->scanned_at;

                    return $enrollment;
                });
        }

        return view('livewire.admin-laporan', compact('events', 'sessions', 'rekap'));
    }
}
