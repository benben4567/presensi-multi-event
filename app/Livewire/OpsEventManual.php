<?php

namespace App\Livewire;

use App\Actions\RecordAttendanceAction;
use App\Enums\AttendanceAction;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OpsEventManual extends Component
{
    public string $eventId;

    public ?int $sessionId = null;

    public string $deviceUuid = '';

    public string $search = '';

    public ?int $selectedEnrollmentId = null;

    public string $action = 'check_in';

    public string $manualNote = '';

    public ?string $resultOutcome = null;

    public string $resultMessage = '';

    public function mount(Event $event): void
    {
        $this->eventId = $event->id;

        $sessions = $event->sessions()->orderBy('start_at')->get();

        if ($sessions->count() === 1) {
            $this->sessionId = $sessions->first()->id;
        }
    }

    public function updatedSessionId(): void
    {
        $this->clearResult();
        $this->selectedEnrollmentId = null;
        $this->search = '';
        $this->manualNote = '';
    }

    public function updatedSearch(): void
    {
        $this->selectedEnrollmentId = null;
        $this->clearResult();
    }

    public function selectEnrollment(int $id): void
    {
        $this->selectedEnrollmentId = $id;
        $this->search = '';
        $this->action = 'check_in';
        $this->manualNote = '';
        $this->clearResult();
    }

    public function clearSelection(): void
    {
        $this->selectedEnrollmentId = null;
        $this->search = '';
        $this->manualNote = '';
    }

    public function submitManual(): void
    {
        $this->validate([
            'sessionId' => ['required', 'integer'],
            'selectedEnrollmentId' => ['required', 'integer'],
            'action' => ['required', 'in:check_in,check_out'],
            'manualNote' => ['nullable', 'string', 'max:200'],
        ]);

        $event = Event::findOrFail($this->eventId);
        $session = EventSession::findOrFail($this->sessionId);
        $enrollment = EventParticipant::findOrFail($this->selectedEnrollmentId);

        $result = (new RecordAttendanceAction)->executeManual(
            $event,
            $session,
            $enrollment,
            AttendanceAction::from($this->action),
            $this->deviceUuid ?: 'unknown',
            Auth::id(),
            $this->manualNote ?: null,
        );

        $this->resultOutcome = $result->outcome;
        $this->resultMessage = $result->message;

        if ($result->isAccepted()) {
            $this->clearSelection();
        }
    }

    public function clearResult(): void
    {
        $this->resultOutcome = null;
        $this->resultMessage = '';
    }

    public function render(): \Illuminate\View\View
    {
        $event = Event::with('sessions')->findOrFail($this->eventId);
        $session = $this->sessionId ? EventSession::find($this->sessionId) : null;

        $enrollments = collect();

        if (mb_strlen($this->search) >= 2) {
            $search = $this->search;
            $enrollments = $event->eventParticipants()
                ->with('participant')
                ->whereHas('participant', function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_e164', 'like', "%{$search}%");
                })
                ->orderBy('id')
                ->limit(10)
                ->get();
        }

        $selectedEnrollment = $this->selectedEnrollmentId
            ? EventParticipant::with('participant')->find($this->selectedEnrollmentId)
            : null;

        $existingCheckIn = false;
        $existingCheckOut = false;

        if ($selectedEnrollment && $this->sessionId) {
            $existingCheckIn = AttendanceLog::where('event_participant_id', $selectedEnrollment->id)
                ->where('session_id', $this->sessionId)
                ->where('action', AttendanceAction::CheckIn->value)
                ->exists();

            $existingCheckOut = AttendanceLog::where('event_participant_id', $selectedEnrollment->id)
                ->where('session_id', $this->sessionId)
                ->where('action', AttendanceAction::CheckOut->value)
                ->exists();
        }

        return view('livewire.ops-event-manual', compact(
            'event',
            'session',
            'enrollments',
            'selectedEnrollment',
            'existingCheckIn',
            'existingCheckOut',
        ))
            ->layout('layouts.ops', [
                'title' => 'Presensi Manual',
                'eventName' => $event->name,
                'hariAktif' => $session?->name,
                'scanRoute' => route('ops.events.scan', $event),
                'manualRoute' => route('ops.events.manual', $event),
                'activeMode' => 'manual',
            ]);
    }
}
