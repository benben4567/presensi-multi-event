<?php

namespace Tests\Feature;

use App\Enums\AttendanceAction;
use App\Livewire\AdminLaporan;
use App\Livewire\AdminPresensi;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private Event $event;

    private EventSession $session;

    private EventParticipant $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator');

        $this->event = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);

        $this->session = EventSession::factory()->for($this->event)->create();

        $this->enrollment = EventParticipant::factory()->for($this->event)->create();
    }

    // ── AdminPresensi — access control ──────────────────────────────────────

    #[Test]
    public function admin_can_access_presensi_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.presensi.index'))
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_access_presensi_page(): void
    {
        $this->get(route('admin.presensi.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_presensi_page(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.presensi.index'))
            ->assertForbidden();
    }

    // ── AdminPresensi — component behaviour ─────────────────────────────────

    #[Test]
    public function presensi_shows_all_logs_without_filter(): void
    {
        AttendanceLog::factory()->create([
            'event_id' => $this->event->id,
            'event_participant_id' => $this->enrollment->id,
            'session_id' => $this->session->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminPresensi::class)
            ->assertSee($this->enrollment->participant->name);
    }

    #[Test]
    public function presensi_filters_by_event(): void
    {
        $otherEvent = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);
        $otherSession = EventSession::factory()->for($otherEvent)->create();
        $otherEnrollment = EventParticipant::factory()->for($otherEvent)->create();

        AttendanceLog::factory()->create([
            'event_id' => $this->event->id,
            'event_participant_id' => $this->enrollment->id,
            'session_id' => $this->session->id,
        ]);

        AttendanceLog::factory()->create([
            'event_id' => $otherEvent->id,
            'event_participant_id' => $otherEnrollment->id,
            'session_id' => $otherSession->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminPresensi::class)
            ->set('eventId', $this->event->id)
            ->assertSee($this->enrollment->participant->name)
            ->assertDontSee($otherEnrollment->participant->name);
    }

    #[Test]
    public function presensi_filters_by_session(): void
    {
        $session2 = EventSession::factory()->for($this->event)->create();

        AttendanceLog::factory()->create([
            'event_id' => $this->event->id,
            'event_participant_id' => $this->enrollment->id,
            'session_id' => $this->session->id,
        ]);

        $enrollment2 = EventParticipant::factory()->for($this->event)->create();
        AttendanceLog::factory()->create([
            'event_id' => $this->event->id,
            'event_participant_id' => $enrollment2->id,
            'session_id' => $session2->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminPresensi::class)
            ->set('eventId', $this->event->id)
            ->set('sessionId', $this->session->id)
            ->assertSee($this->enrollment->participant->name)
            ->assertDontSee($enrollment2->participant->name);
    }

    #[Test]
    public function changing_event_resets_session(): void
    {
        $otherEvent = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminPresensi::class)
            ->set('eventId', $this->event->id)
            ->set('sessionId', $this->session->id)
            ->set('eventId', $otherEvent->id)
            ->assertSet('sessionId', null);
    }

    // ── AdminLaporan — access control ───────────────────────────────────────

    #[Test]
    public function admin_can_access_laporan_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index'))
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_access_laporan_page(): void
    {
        $this->get(route('admin.laporan.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_laporan_page(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.laporan.index'))
            ->assertForbidden();
    }

    // ── AdminLaporan — component behaviour ──────────────────────────────────

    #[Test]
    public function laporan_shows_enrollments_with_attendance_when_filters_set(): void
    {
        AttendanceLog::factory()->create([
            'event_id' => $this->event->id,
            'event_participant_id' => $this->enrollment->id,
            'session_id' => $this->session->id,
            'action' => AttendanceAction::CheckIn,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminLaporan::class)
            ->set('eventId', $this->event->id)
            ->set('sessionId', $this->session->id)
            ->assertSee($this->enrollment->participant->name)
            ->assertSee('Hadir');
    }

    #[Test]
    public function laporan_shows_tidak_hadir_for_missing_check_in(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminLaporan::class)
            ->set('eventId', $this->event->id)
            ->set('sessionId', $this->session->id)
            ->assertSee($this->enrollment->participant->name)
            ->assertSee('Tidak Hadir');
    }

    #[Test]
    public function laporan_requires_both_filters_before_showing_data(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminLaporan::class)
            ->assertSee('Pilih event dan hari');
    }

    #[Test]
    public function changing_event_on_laporan_resets_session(): void
    {
        $otherEvent = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminLaporan::class)
            ->set('eventId', $this->event->id)
            ->set('sessionId', $this->session->id)
            ->set('eventId', $otherEvent->id)
            ->assertSet('sessionId', null);
    }

    // ── LaporanExportController ──────────────────────────────────────────────

    #[Test]
    public function export_returns_excel_file(): void
    {
        AttendanceLog::factory()->create([
            'event_id' => $this->event->id,
            'event_participant_id' => $this->enrollment->id,
            'session_id' => $this->session->id,
            'action' => AttendanceAction::CheckIn,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.export', [
                'event_id' => $this->event->id,
                'session_id' => $this->session->id,
            ]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=laporan-presensi-'.str($this->event->name)->slug().'-'.str($this->session->name)->slug().'.xlsx');
    }

    #[Test]
    public function export_requires_valid_event_and_session(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.export', [
                'event_id' => 9999,
                'session_id' => 9999,
            ]))
            ->assertRedirect();
    }

    #[Test]
    public function guest_cannot_download_export(): void
    {
        $this->get(route('admin.laporan.export', [
            'event_id' => $this->event->id,
            'session_id' => $this->session->id,
        ]))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_download_export(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.laporan.export', [
                'event_id' => $this->event->id,
                'session_id' => $this->session->id,
            ]))
            ->assertForbidden();
    }
}
