<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\EventSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Event')]
class AdminEventForm extends Component
{
    public ?string $eventId = null;

    public string $code = '';

    public string $name = '';

    public string $startAt = '';

    public string $endAt = '';

    public string $status = 'draft';

    public bool $enableCheckout = false;

    /** @var array<int, string> */
    public array $operatorDisplayFields = ['name', 'phone_e164'];

    /** Tambahan field meta, pisahkan dengan koma. */
    public string $extraDisplayFields = '';

    public function mount(?Event $event = null): void
    {
        if ($event && $event->exists) {
            $this->eventId = $event->id;
            $this->code = $event->code ?? '';
            $this->name = $event->name;
            $this->startAt = $event->start_at->format('Y-m-d\TH:i');
            $this->endAt = $event->end_at->format('Y-m-d\TH:i');
            $this->status = $event->status->value;
            $this->enableCheckout = (bool) ($event->settings['enable_checkout'] ?? false);

            $allFields = $event->settings['operator_display_fields'] ?? ['name', 'phone_e164'];
            $predefined = ['name', 'phone_e164'];
            $this->operatorDisplayFields = array_values(array_intersect($allFields, $predefined));
            $extra = array_diff($allFields, $predefined);
            $this->extraDisplayFields = implode(', ', $extra);
        }
    }

    public function save(): void
    {
        $this->validate([
            'code' => ['nullable', 'string', 'max:10',
                Rule::unique('events', 'code')->ignore($this->eventId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'startAt' => ['required', 'date'],
            'endAt' => ['required', 'date', 'after_or_equal:startAt'],
            'status' => ['required', Rule::in(['draft', 'open', 'closed'])],
            'enableCheckout' => ['boolean'],
            'operatorDisplayFields' => ['array'],
            'operatorDisplayFields.*' => ['string'],
            'extraDisplayFields' => ['nullable', 'string', 'max:500'],
        ]);

        $displayFields = $this->buildDisplayFields();

        $data = [
            'code' => $this->code ?: null,
            'name' => $this->name,
            'start_at' => Carbon::parse($this->startAt),
            'end_at' => Carbon::parse($this->endAt),
            'status' => $this->status,
            'settings' => [
                'enable_checkout' => $this->enableCheckout,
                'operator_display_fields' => $displayFields,
            ],
            'updated_by' => Auth::id(),
        ];

        if ($this->eventId) {
            $event = Event::findOrFail($this->eventId);
            $event->update($data);
        } else {
            $data['created_by'] = Auth::id();
            $event = Event::create($data);
        }

        $this->syncSessions($event);

        $this->dispatch('toast',
            message: $this->eventId ? 'Event berhasil diperbarui.' : 'Event berhasil dibuat.',
            type: 'success',
        );

        $this->redirect(route('admin.events.index'), navigate: true);
    }

    public function openAttendance(int $minutes): void
    {
        if (! $this->eventId) {
            return;
        }

        $event = Event::findOrFail($this->eventId);
        $event->update(['override_until' => now()->addMinutes($minutes)]);

        $this->dispatch('toast',
            message: "Presensi dibuka selama {$minutes} menit.",
            type: 'success',
        );
    }

    /** @return array<int, string> */
    private function buildDisplayFields(): array
    {
        $extra = collect(explode(',', $this->extraDisplayFields))
            ->map(fn ($f) => trim($f))
            ->filter()
            ->values()
            ->toArray();

        return array_values(array_unique(array_merge($this->operatorDisplayFields, $extra)));
    }

    private function syncSessions(Event $event): void
    {
        $start = $event->start_at->copy()->startOfDay();
        $end = $event->end_at->copy()->startOfDay();

        $expectedDates = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $expectedDates[] = $dateStr;

            EventSession::firstOrCreate(
                ['event_id' => $event->id, 'start_at' => $current->copy()->startOfDay()],
                [
                    'name' => $this->indonesianDate($current),
                    'end_at' => $current->copy()->endOfDay(),
                    'type' => 'day',
                ],
            );

            $current->addDay();
        }

        // Remove sessions outside the new date range only if they have no attendance logs.
        $sessionsToDelete = $event->sessions()
            ->get()
            ->filter(fn ($s) => ! in_array($s->start_at->toDateString(), $expectedDates))
            ->filter(fn ($s) => $s->attendanceLogs()->doesntExist())
            ->pluck('id');

        if ($sessionsToDelete->isNotEmpty()) {
            EventSession::whereIn('id', $sessionsToDelete)->delete();
        }
    }

    private function indonesianDate(Carbon $date): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $days[$date->dayOfWeek].', '.$date->day.' '.$months[$date->month - 1].' '.$date->year;
    }

    public function render(): \Illuminate\View\View
    {
        $sessions = $this->eventId
            ? Event::findOrFail($this->eventId)->sessions()->orderBy('start_at')->get()
            : collect();

        $event = $this->eventId ? Event::find($this->eventId) : null;

        return view('livewire.admin-event-form', compact('sessions', 'event'));
    }
}
