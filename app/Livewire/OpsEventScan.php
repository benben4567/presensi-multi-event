<?php

namespace App\Livewire;

use App\Actions\AttendanceScanResult;
use App\Actions\RecordAttendanceAction;
use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OpsEventScan extends Component
{
    public string $eventId;

    public ?int $sessionId = null;

    public string $deviceUuid = '';

    /** @var list<array{outcome: string, message: string, name: string|null, time: string}> */
    public array $recentScans = [];

    public ?string $resultOutcome = null;

    public string $resultMessage = '';

    public ?string $resultName = null;

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
    }

    public function processQrValue(string $raw): void
    {
        $raw = trim($raw);

        if ($raw === '' || $this->sessionId === null) {
            return;
        }

        $event = Event::findOrFail($this->eventId);
        $session = EventSession::findOrFail($this->sessionId);

        $result = (new RecordAttendanceAction)->executeQr(
            $event,
            $session,
            $raw,
            $this->deviceUuid ?: 'unknown',
            Auth::id(),
        );

        $name = $result->log?->load('eventParticipant.participant')?->eventParticipant?->participant?->name;

        $this->applyResult($result, $name);
    }

    public function clearResult(): void
    {
        $this->resultOutcome = null;
        $this->resultMessage = '';
        $this->resultName = null;
    }

    private function applyResult(AttendanceScanResult $result, ?string $name): void
    {
        $this->resultOutcome = $result->outcome;
        $this->resultMessage = $result->message;
        $this->resultName = $name;

        array_unshift($this->recentScans, [
            'outcome' => $result->outcome,
            'message' => $result->message,
            'name' => $name,
            'time' => now()->format('H:i:s'),
        ]);

        $this->recentScans = array_slice($this->recentScans, 0, 8);
    }

    public function render(): \Illuminate\View\View
    {
        $event = Event::with('sessions')->findOrFail($this->eventId);
        $session = $this->sessionId ? EventSession::find($this->sessionId) : null;

        return view('livewire.ops-event-scan', compact('event', 'session'))
            ->layout('layouts.ops', [
                'title' => 'Scan QR',
                'eventName' => $event->name,
                'hariAktif' => $session?->name,
                'scanRoute' => route('ops.events.scan', $event),
                'manualRoute' => route('ops.events.manual', $event),
                'activeMode' => 'scan',
            ]);
    }
}
