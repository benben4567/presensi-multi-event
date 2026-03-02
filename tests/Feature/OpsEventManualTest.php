<?php

namespace Tests\Feature;

use App\Enums\AttendanceAction;
use App\Livewire\OpsEventManual;
use App\Models\Device;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpsEventManualTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private User $admin;

    private Event $event;

    private EventSession $session;

    private EventParticipant $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->event = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);

        $this->session = EventSession::factory()->for($this->event)->create();

        $this->enrollment = EventParticipant::factory()->for($this->event)->create();
    }

    // ── Access control ──────────────────────────────────────────────────────

    #[Test]
    public function operator_can_access_manual_page(): void
    {
        $this->actingAs($this->operator)
            ->get(route('ops.events.manual', $this->event))
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_access_manual_page(): void
    {
        $this->get(route('ops.events.manual', $this->event))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_cannot_access_operator_manual_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ops.events.manual', $this->event))
            ->assertForbidden();
    }

    // ── Mount ───────────────────────────────────────────────────────────────

    #[Test]
    public function component_auto_selects_single_session_on_mount(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->assertSet('sessionId', $this->session->id);
    }

    // ── Enrollment selection ────────────────────────────────────────────────

    #[Test]
    public function selecting_enrollment_clears_search_and_result(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->set('search', 'Budi')
            ->call('selectEnrollment', $this->enrollment->id)
            ->assertSet('selectedEnrollmentId', $this->enrollment->id)
            ->assertSet('search', '')
            ->assertSet('resultOutcome', null);
    }

    #[Test]
    public function clear_selection_resets_state(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->assertSet('selectedEnrollmentId', $this->enrollment->id)
            ->call('clearSelection')
            ->assertSet('selectedEnrollmentId', null)
            ->assertSet('search', '');
    }

    // ── Manual submit ───────────────────────────────────────────────────────

    #[Test]
    public function check_in_accepted_and_creates_attendance_log(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->set('action', 'check_in')
            ->call('submitManual')
            ->assertSet('resultOutcome', 'accepted');

        $this->assertDatabaseHas('attendance_logs', [
            'event_participant_id' => $this->enrollment->id,
            'session_id' => $this->session->id,
            'action' => AttendanceAction::CheckIn->value,
        ]);
    }

    #[Test]
    public function check_out_accepted_after_check_in(): void
    {
        // First check-in
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->set('action', 'check_in')
            ->call('submitManual');

        // Then check-out
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->set('action', 'check_out')
            ->call('submitManual')
            ->assertSet('resultOutcome', 'accepted');

        $this->assertDatabaseCount('attendance_logs', 2);
    }

    #[Test]
    public function checkout_without_checkin_gives_warning(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->set('action', 'check_out')
            ->call('submitManual')
            ->assertSet('resultOutcome', 'warning');

        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function manual_note_is_stored_in_scan_attempt(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->set('action', 'check_in')
            ->set('manualNote', 'Terlambat masuk')
            ->call('submitManual');

        $this->assertDatabaseHas('scan_attempts', [
            'source' => 'manual',
            'manual_note' => 'Terlambat masuk',
        ]);
    }

    #[Test]
    public function accepted_check_in_clears_selection(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->set('action', 'check_in')
            ->call('submitManual')
            ->assertSet('selectedEnrollmentId', null)
            ->assertSet('search', '');
    }

    #[Test]
    public function submit_requires_session(): void
    {
        // Two sessions so no auto-select
        $event = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);
        EventSession::factory()->for($event)->create();
        EventSession::factory()->for($event)->create();
        $enrollment = EventParticipant::factory()->for($event)->create();

        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $event])
            ->call('selectEnrollment', $enrollment->id)
            ->call('submitManual')
            ->assertHasErrors(['sessionId']);
    }

    #[Test]
    public function device_is_created_on_accepted_scan(): void
    {
        $this->assertDatabaseCount('devices', 0);

        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->set('action', 'check_in')
            ->call('submitManual');

        // Device 'unknown' is created since no deviceUuid was set in test
        $this->assertDatabaseCount('devices', 1);
    }

    #[Test]
    public function manual_note_max_200_chars(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventManual::class, ['event' => $this->event])
            ->call('selectEnrollment', $this->enrollment->id)
            ->set('manualNote', str_repeat('x', 201))
            ->call('submitManual')
            ->assertHasErrors(['manualNote']);
    }
}
