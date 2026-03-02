<?php

namespace Tests\Feature;

use App\Enums\ScanResultCode;
use App\Livewire\AdminDashboard;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\ScanAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator');
    }

    // ── Access control ───────────────────────────────────────────────────────

    #[Test]
    public function admin_can_access_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_is_forbidden_from_dashboard(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    // ── Snapshot cards ───────────────────────────────────────────────────────

    #[Test]
    public function dashboard_renders_with_zero_counts_when_no_active_events(): void
    {
        Event::factory()->closed()->create();

        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);

        $this->assertCount(0, $component->viewData('activeEvents'));
        $this->assertEquals(0, $component->viewData('snapshotCheckins'));
        $this->assertEquals(0, $component->viewData('snapshotWarnings'));
        $this->assertEquals(0, $component->viewData('snapshotRejected'));
    }

    #[Test]
    public function dashboard_counts_only_open_events_active_today(): void
    {
        // Active today — counted
        Event::factory()->create([
            'status' => 'open',
            'start_at' => now()->startOfDay(),
            'end_at' => now()->endOfDay(),
        ]);

        // Draft event — not counted
        Event::factory()->create([
            'status' => 'draft',
            'start_at' => now()->startOfDay(),
            'end_at' => now()->endOfDay(),
        ]);

        // Open but starts tomorrow — not counted
        Event::factory()->open()->create([
            'start_at' => now()->addDay()->startOfDay(),
            'end_at' => now()->addDay()->endOfDay(),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);

        $this->assertCount(1, $component->viewData('activeEvents'));
    }

    #[Test]
    public function dashboard_counts_checkins_from_todays_sessions_only(): void
    {
        $event = $this->createActiveTodayEvent();
        $session = $this->createTodaySession($event);
        $enrollment = EventParticipant::factory()->for($event)->create();
        $device = Device::factory()->create();

        // Check-in for today's session — counted
        AttendanceLog::factory()->create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'action' => 'check_in',
            'scanned_at' => now(),
            'device_id' => $device->id,
            'operator_user_id' => $this->admin->id,
        ]);

        // Check-in for yesterday's session — not counted
        $yesterdaySession = EventSession::factory()->for($event)->create([
            'start_at' => now()->subDay()->startOfDay(),
            'end_at' => now()->subDay()->endOfDay(),
        ]);

        AttendanceLog::factory()->create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $yesterdaySession->id,
            'action' => 'check_in',
            'scanned_at' => now()->subDay(),
            'device_id' => $device->id,
            'operator_user_id' => $this->admin->id,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);

        $this->assertEquals(1, $component->viewData('snapshotCheckins'));
    }

    #[Test]
    public function dashboard_counts_warnings_and_rejections_for_active_events(): void
    {
        $event = $this->createActiveTodayEvent();
        $session = $this->createTodaySession($event);
        $enrollment = EventParticipant::factory()->for($event)->create();

        ScanAttempt::factory()->count(2)->warning()->create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'scanned_at' => now(),
        ]);

        ScanAttempt::factory()->count(3)->rejected()->create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'scanned_at' => now(),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);

        $this->assertEquals(2, $component->viewData('snapshotWarnings'));
        $this->assertEquals(3, $component->viewData('snapshotRejected'));
    }

    #[Test]
    public function dashboard_does_not_count_scans_from_inactive_events(): void
    {
        $closedEvent = Event::factory()->closed()->create();
        $session = EventSession::factory()->for($closedEvent)->create([
            'start_at' => now()->subDay()->startOfDay(),
            'end_at' => now()->subDay()->endOfDay(),
        ]);
        $enrollment = EventParticipant::factory()->for($closedEvent)->create();

        ScanAttempt::factory()->count(5)->rejected()->create([
            'event_id' => $closedEvent->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'scanned_at' => now(),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);

        $this->assertEquals(0, $component->viewData('snapshotRejected'));
    }

    // ── Hourly trend ─────────────────────────────────────────────────────────

    #[Test]
    public function hourly_data_always_has_24_buckets(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);

        $this->assertCount(24, $component->viewData('hourlyData'));
    }

    #[Test]
    public function hourly_data_buckets_correctly_aggregated(): void
    {
        $event = $this->createActiveTodayEvent();
        $session = $this->createTodaySession($event);
        $enrollment = EventParticipant::factory()->for($event)->create();

        ScanAttempt::factory()->count(2)->rejected()->create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'scanned_at' => now()->setHour(10)->setMinute(0),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);
        $hourlyData = $component->viewData('hourlyData');

        $this->assertEquals(2, $hourlyData[10]['rejected']);
        $this->assertEquals(0, $hourlyData[10]['warning']);
        $this->assertEquals(0, $hourlyData[11]['rejected']);
    }

    // ── Top 5 rejection reasons ───────────────────────────────────────────────

    #[Test]
    public function top_rejected_is_empty_when_no_rejections(): void
    {
        $this->createActiveTodayEvent();

        Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class)
            ->assertSee('Tidak ada penolakan hari ini.');
    }

    #[Test]
    public function top_rejected_lists_most_frequent_code_first(): void
    {
        $event = $this->createActiveTodayEvent();
        $session = $this->createTodaySession($event);
        $enrollment = EventParticipant::factory()->for($event)->create();

        // 3 token_expired
        ScanAttempt::factory()->count(3)->rejected()->create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'code' => ScanResultCode::TokenExpired->value,
            'scanned_at' => now(),
        ]);

        // 1 token_revoked
        ScanAttempt::factory()->rejected()->create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'code' => ScanResultCode::TokenRevoked->value,
            'scanned_at' => now(),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);
        $topRejected = $component->viewData('topRejected');

        $this->assertCount(2, $topRejected);
        // code is cast to ScanResultCode enum by the model
        $this->assertEquals(ScanResultCode::TokenExpired, $topRejected->first()->code);
        $this->assertEquals(3, $topRejected->first()->total);
    }

    // ── Event table ───────────────────────────────────────────────────────────

    #[Test]
    public function active_events_table_shows_event_name(): void
    {
        Event::factory()->create([
            'status' => 'open',
            'name' => 'Seminar Nasional 2026',
            'start_at' => now()->startOfDay(),
            'end_at' => now()->endOfDay(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class)
            ->assertSee('Seminar Nasional 2026');
    }

    #[Test]
    public function active_events_table_shows_empty_state_when_no_events(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class)
            ->assertSee('Tidak ada event aktif hari ini.');
    }

    #[Test]
    public function active_events_table_shows_override_button_only_for_past_end_time(): void
    {
        // Event that ended 1 hour ago but is still "open" — override button shown
        Event::factory()->create([
            'status' => 'open',
            'start_at' => now()->subDays(2)->startOfDay(),
            'end_at' => now()->subHour(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class)
            ->assertSee('Override');
    }

    // ── Operational alerts ────────────────────────────────────────────────────

    #[Test]
    public function alert_panel_shows_no_override_message_when_none_active(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class)
            ->assertSee('Tidak ada override aktif.');
    }

    #[Test]
    public function alert_panel_shows_active_override_event_name(): void
    {
        Event::factory()->create([
            'status' => 'open',
            'name' => 'Event Override Test',
            'start_at' => now()->subDay(),
            'end_at' => now()->subHour(),
            'override_until' => now()->addMinutes(30),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class)
            ->assertSee('Event Override Test');
    }

    // ── Monitoring mini ───────────────────────────────────────────────────────

    #[Test]
    public function monitoring_mini_shows_queue_not_used(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class)
            ->assertSee('Belum digunakan');
    }

    #[Test]
    public function monitoring_mini_shows_today_activity_log_count(): void
    {
        activity()->log('Aksi test hari ini');

        $component = Livewire::actingAs($this->admin)
            ->test(AdminDashboard::class);

        $this->assertEquals(1, $component->viewData('activityLogCount'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createActiveTodayEvent(): Event
    {
        return Event::factory()->create([
            'status' => 'open',
            'start_at' => now()->startOfDay(),
            'end_at' => now()->endOfDay(),
        ]);
    }

    private function createTodaySession(Event $event): EventSession
    {
        return EventSession::factory()->for($event)->create([
            'start_at' => now()->startOfDay(),
            'end_at' => now()->endOfDay(),
        ]);
    }
}
