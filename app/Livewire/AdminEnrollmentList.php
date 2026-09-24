<?php

namespace App\Livewire;

use App\Actions\EnrollParticipantAction;
use App\Actions\SetEnrollmentAccessAction;
use App\Enums\AccessStatus;
use App\Jobs\SendInvitationCardEmailJob;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Support\PhoneNumberNormalizer;
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

    public string $statusFilter = '';

    /** @var list<int> */
    public array $selected = [];

    public bool $selectAll = false;

    // ── Blacklist modal state ──────────────────────────────────────────────

    public bool $showBlacklistForm = false;

    public ?int $pendingEnrollmentId = null;

    public string $blacklistReason = '';

    // ── Add participant modal state ─────────────────────────────────────────

    public bool $showAddForm = false;

    public string $newName = '';

    public string $newPhone = '';

    public string $newEmail = '';

    /** @var array<int, array{key: string, value: string}> */
    public array $newMeta = [];

    // ── Edit participant modal state ────────────────────────────────────────

    public bool $showEditForm = false;

    public ?int $editingEnrollmentId = null;

    public string $editName = '';

    public string $editPhone = '';

    public string $editEmail = '';

    // ── Lihat QR modal state ─────────────────────────────────────────────────

    public bool $showQrModal = false;

    public ?int $viewingQrEnrollmentId = null;

    public function mount(Event $event): void
    {
        $this->eventId = $event->id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value ? $this->visibleEnrollmentIds() : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    /** @return list<int> */
    private function visibleEnrollmentIds(): array
    {
        return $this->buildEnrollmentsQuery()->pluck('id')->all();
    }

    // ── Bulk actions ─────────────────────────────────────────────────────

    public function confirmBulkDisable(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->dispatch('show-confirm',
            message: count($this->selected).' peserta terpilih akan dinonaktifkan. QR mereka akan dicabut sementara.',
            confirmedEvent: 'bulk-disable-enrollment',
            confirmedData: ['ids' => $this->selected],
            confirmLabel: 'Nonaktifkan',
            cancelLabel: 'Batal',
        );
    }

    #[On('bulk-disable-enrollment')]
    public function bulkDisable(array $ids): void
    {
        $this->bulkSetAccess($ids, AccessStatus::Disabled);
        $this->dispatch('toast', message: 'Peserta terpilih berhasil dinonaktifkan.', type: 'warning');
    }

    public function confirmBulkEnable(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->dispatch('show-confirm',
            message: count($this->selected).' peserta terpilih akan diaktifkan kembali.',
            confirmedEvent: 'bulk-enable-enrollment',
            confirmedData: ['ids' => $this->selected],
            confirmLabel: 'Aktifkan Kembali',
            cancelLabel: 'Batal',
        );
    }

    #[On('bulk-enable-enrollment')]
    public function bulkEnable(array $ids): void
    {
        $this->bulkSetAccess($ids, AccessStatus::Allowed);
        $this->dispatch('toast', message: 'Peserta terpilih berhasil diaktifkan kembali.', type: 'success');
    }

    /** @param list<int> $ids */
    private function bulkSetAccess(array $ids, AccessStatus $status): void
    {
        $enrollments = EventParticipant::query()
            ->where('event_id', $this->eventId)
            ->whereIn('id', $ids)
            ->get();

        foreach ($enrollments as $enrollment) {
            (new SetEnrollmentAccessAction)->execute($enrollment, $status, null, Auth::id());
        }

        $this->clearSelection();
    }

    // ── Kirim undangan via email ─────────────────────────────────────────

    public function confirmSendInvitationEmails(): void
    {
        $count = $this->eligibleForEmailQuery()->count();

        if ($count === 0) {
            $this->dispatch('toast', message: 'Tidak ada peserta dengan email yang bisa dikirimi undangan.', type: 'warning');

            return;
        }

        $this->dispatch('show-confirm',
            message: "Kirim undangan ke {$count} peserta yang punya email? Proses berjalan di latar belakang.",
            confirmedEvent: 'send-invitation-emails',
            confirmedData: [],
            confirmLabel: 'Kirim',
            cancelLabel: 'Batal',
        );
    }

    #[On('send-invitation-emails')]
    public function sendInvitationEmails(): void
    {
        $eligible = $this->eligibleForEmailQuery()->get();

        foreach ($eligible as $ep) {
            SendInvitationCardEmailJob::dispatch($ep->id);
        }

        $totalAllowed = EventParticipant::where('event_id', $this->eventId)
            ->where('access_status', AccessStatus::Allowed->value)
            ->count();
        $skipped = $totalAllowed - $eligible->count();

        $message = "Undangan dikirim ke antrean untuk {$eligible->count()} peserta.";
        if ($skipped > 0) {
            $message .= " {$skipped} peserta dilewati (belum ada email atau undangan tidak aktif).";
        }

        $this->dispatch('toast', message: $message, type: $skipped > 0 ? 'warning' : 'success');
    }

    /**
     * Enrollments eligible to receive an invitation email: allowed access,
     * a valid (non-revoked) invitation, and a participant email on file.
     *
     * @return \Illuminate\Database\Eloquent\Builder<EventParticipant>
     */
    private function eligibleForEmailQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return EventParticipant::query()
            ->where('event_id', $this->eventId)
            ->where('access_status', AccessStatus::Allowed->value)
            ->whereHas('participant', fn ($q) => $q->whereNotNull('email'))
            ->whereHas('invitation', fn ($q) => $q->whereNotNull('token')->whereNull('revoked_at'));
    }

    // ── Lihat QR ──────────────────────────────────────────────────────────

    public function viewQr(int $enrollmentId): void
    {
        $this->viewingQrEnrollmentId = $enrollmentId;
        $this->showQrModal = true;
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->viewingQrEnrollmentId = null;
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
        $enrollment = $this->enrollmentInThisEvent($enrollmentId);
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
        $enrollment = $this->enrollmentInThisEvent($enrollmentId);
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

        $enrollment = $this->enrollmentInThisEvent((int) $this->pendingEnrollmentId);
        (new SetEnrollmentAccessAction)->execute(
            $enrollment,
            AccessStatus::Blacklisted,
            $this->blacklistReason,
            Auth::id(),
        );

        $this->cancelBlacklist();
        $this->dispatch('toast', message: 'Peserta berhasil diblacklist.', type: 'error');
    }

    // ── Add participant ──────────────────────────────────────────────────

    public function openAddForm(): void
    {
        $this->reset(['newName', 'newPhone', 'newEmail']);
        $this->newMeta = [['key' => '', 'value' => '']];
        $this->resetErrorBag();
        $this->showAddForm = true;
    }

    public function cancelAddForm(): void
    {
        $this->showAddForm = false;
        $this->reset(['newName', 'newPhone', 'newEmail', 'newMeta']);
    }

    public function addMetaField(): void
    {
        $this->newMeta[] = ['key' => '', 'value' => ''];
    }

    public function removeMetaField(int $index): void
    {
        unset($this->newMeta[$index]);
        $this->newMeta = array_values($this->newMeta);
    }

    public function confirmAdd(): void
    {
        $this->validate([
            'newName' => ['required', 'string', 'max:150'],
            'newPhone' => ['required', 'string'],
            'newEmail' => ['nullable', 'email', 'max:255'],
        ], [], ['newName' => 'Nama', 'newPhone' => 'No HP', 'newEmail' => 'Email']);

        $phoneE164 = PhoneNumberNormalizer::toE164($this->newPhone);

        if ($phoneE164 === null) {
            $this->addError('newPhone', 'Nomor HP tidak valid.');

            return;
        }

        $meta = [];
        foreach ($this->newMeta as $row) {
            $key = strtolower(trim($row['key'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($key !== '' && ! in_array($key, ['nama', 'no_hp'], true) && $value !== '') {
                $meta[$key] = $value;
            }
        }

        $event = Event::findOrFail($this->eventId);
        $participant = Participant::where('phone_e164', $phoneE164)->first();

        if ($participant) {
            $alreadyEnrolled = EventParticipant::where('event_id', $event->id)
                ->where('participant_id', $participant->id)
                ->exists();

            if ($alreadyEnrolled) {
                $this->addError('newPhone', 'Peserta dengan nomor ini sudah terdaftar di event ini.');

                return;
            }

            $updates = $this->newEmail !== '' ? ['email' => $this->newEmail] : [];
            if (! empty($meta)) {
                $updates['meta'] = array_merge($participant->meta ?? [], $meta);
            }
            if (! empty($updates)) {
                $participant->update($updates);
            }
        } else {
            $participant = Participant::create([
                'name' => $this->newName,
                'phone_e164' => $phoneE164,
                'email' => $this->newEmail !== '' ? $this->newEmail : null,
                'meta' => empty($meta) ? null : $meta,
            ]);
        }

        (new EnrollParticipantAction)->execute($event, $participant);

        $this->cancelAddForm();
        $this->dispatch('toast', message: 'Peserta berhasil ditambahkan.', type: 'success');
    }

    // ── Edit participant ─────────────────────────────────────────────────

    public function openEditForm(int $enrollmentId): void
    {
        $enrollment = $this->enrollmentInThisEvent($enrollmentId, with: 'participant');

        $this->editingEnrollmentId = $enrollmentId;
        $this->editName = $enrollment->participant->name;
        $this->editPhone = $enrollment->participant->phone_e164 ?? '';
        $this->editEmail = $enrollment->participant->email ?? '';
        $this->resetErrorBag();
        $this->showEditForm = true;
    }

    public function cancelEditForm(): void
    {
        $this->showEditForm = false;
        $this->reset(['editingEnrollmentId', 'editName', 'editPhone', 'editEmail']);
    }

    public function confirmEdit(): void
    {
        $this->validate([
            'editName' => ['required', 'string', 'max:150'],
            'editPhone' => ['required', 'string'],
            'editEmail' => ['nullable', 'email', 'max:255'],
        ], [], ['editName' => 'Nama', 'editPhone' => 'No HP', 'editEmail' => 'Email']);

        $phoneE164 = PhoneNumberNormalizer::toE164($this->editPhone);

        if ($phoneE164 === null) {
            $this->addError('editPhone', 'Nomor HP tidak valid.');

            return;
        }

        $enrollment = $this->enrollmentInThisEvent((int) $this->editingEnrollmentId, with: 'participant');
        $participant = $enrollment->participant;

        $collision = Participant::where('phone_e164', $phoneE164)
            ->where('id', '!=', $participant->id)
            ->first();

        if ($collision) {
            $this->addError('editPhone', "Nomor ini sudah dipakai peserta lain ({$collision->name}).");

            return;
        }

        $participant->update([
            'name' => $this->editName,
            'phone_e164' => $phoneE164,
            'email' => $this->editEmail !== '' ? $this->editEmail : null,
        ]);

        $this->cancelEditForm();
        $this->dispatch('toast', message: 'Data peserta berhasil diperbarui.', type: 'success');
    }

    /**
     * Fetch an EventParticipant, scoped to this component's event — prevents
     * an id belonging to another event from being mutated via a crafted
     * Livewire request.
     */
    private function enrollmentInThisEvent(int $enrollmentId, ?string $with = null): EventParticipant
    {
        return EventParticipant::query()
            ->when($with, fn ($q) => $q->with($with))
            ->where('event_id', $this->eventId)
            ->findOrFail($enrollmentId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<EventParticipant>
     */
    private function buildEnrollmentsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return EventParticipant::query()
            ->where('event_id', $this->eventId)
            ->when($this->search, function ($query): void {
                $search = $this->search;
                $query->whereHas('participant', fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_e164', 'like', "%{$search}%")
                );
            })
            ->when($this->statusFilter, fn ($query) => $query->where('access_status', $this->statusFilter));
    }

    public function render(): \Illuminate\View\View
    {
        $event = Event::findOrFail($this->eventId);

        $enrollments = $this->buildEnrollmentsQuery()
            ->with(['participant', 'invitation'])
            ->latest()
            ->paginate(20);

        $viewingQr = $this->viewingQrEnrollmentId
            ? EventParticipant::with(['participant', 'invitation'])->find($this->viewingQrEnrollmentId)
            : null;

        return view('livewire.admin-enrollment-list', compact('event', 'enrollments', 'viewingQr'));
    }
}
