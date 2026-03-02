<?php

namespace App\Livewire;

use App\Actions\SetEnrollmentAccessAction;
use App\Enums\AccessStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Peserta')]
class AdminEnrollmentList extends Component
{
    use WithPagination;

    public string $eventId;

    public string $search = '';

    // ── Blacklist modal state ──────────────────────────────────────────────

    public bool $showBlacklistForm = false;

    public ?int $pendingEnrollmentId = null;

    public string $blacklistReason = '';

    public function mount(Event $event): void
    {
        $this->eventId = $event->id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // ── Disable ───────────────────────────────────────────────────────────

    public function confirmDisable(int $enrollmentId): void
    {
        $this->dispatch('show-confirm',
            message: 'Nonaktifkan peserta ini? QR mereka akan dicabut sementara.',
            confirmedEvent: 'disable-enrollment',
            confirmedData: ['enrollmentId' => $enrollmentId],
            confirmLabel: 'Nonaktifkan',
            cancelLabel: 'Batal',
        );
    }

    #[On('disable-enrollment')]
    public function disable(int $enrollmentId): void
    {
        $enrollment = EventParticipant::findOrFail($enrollmentId);
        (new SetEnrollmentAccessAction)->execute($enrollment, AccessStatus::Disabled, null, Auth::id());
        $this->dispatch('toast', message: 'Peserta berhasil dinonaktifkan.', type: 'warning');
    }

    // ── Enable ────────────────────────────────────────────────────────────

    public function confirmEnable(int $enrollmentId): void
    {
        $this->dispatch('show-confirm',
            message: 'Aktifkan kembali peserta ini? QR mereka akan berlaku lagi.',
            confirmedEvent: 'enable-enrollment',
            confirmedData: ['enrollmentId' => $enrollmentId],
            confirmLabel: 'Aktifkan Kembali',
            cancelLabel: 'Batal',
        );
    }

    #[On('enable-enrollment')]
    public function enable(int $enrollmentId): void
    {
        $enrollment = EventParticipant::findOrFail($enrollmentId);
        (new SetEnrollmentAccessAction)->execute($enrollment, AccessStatus::Allowed, null, Auth::id());
        $this->dispatch('toast', message: 'Peserta berhasil diaktifkan kembali.', type: 'success');
    }

    // ── Blacklist (needs reason) ───────────────────────────────────────────

    public function openBlacklist(int $enrollmentId): void
    {
        $this->pendingEnrollmentId = $enrollmentId;
        $this->blacklistReason = '';
        $this->showBlacklistForm = true;
    }

    public function cancelBlacklist(): void
    {
        $this->pendingEnrollmentId = null;
        $this->blacklistReason = '';
        $this->showBlacklistForm = false;
    }

    public function confirmBlacklist(): void
    {
        $this->validate([
            'blacklistReason' => ['required', 'string', 'max:100'],
        ]);

        $enrollment = EventParticipant::findOrFail($this->pendingEnrollmentId);
        (new SetEnrollmentAccessAction)->execute(
            $enrollment,
            AccessStatus::Blacklisted,
            $this->blacklistReason,
            Auth::id(),
        );

        $this->cancelBlacklist();
        $this->dispatch('toast', message: 'Peserta berhasil diblacklist.', type: 'error');
    }

    public function render(): \Illuminate\View\View
    {
        $event = Event::findOrFail($this->eventId);

        $enrollments = $event->eventParticipants()
            ->with(['participant', 'invitation'])
            ->when($this->search, function ($query): void {
                $search = $this->search;
                $query->whereHas('participant', fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_e164', 'like', "%{$search}%")
                );
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin-enrollment-list', compact('event', 'enrollments'));
    }
}
