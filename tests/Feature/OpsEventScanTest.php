<?php

namespace Tests\Feature;

use App\Livewire\OpsEventScan;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpsEventScanTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private User $admin;

    private Event $event;

    private EventSession $session;

    private EventParticipant $enrollment;

    private string $rawToken;

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

        $this->rawToken = bin2hex(random_bytes(32));

        Invitation::factory()->for($this->enrollment, 'eventParticipant')->create([
            'token_hash' => hash('sha256', $this->rawToken),
            'token' => $this->rawToken,
            'issued_at' => now()->subHour(),
            'expires_at' => now()->addDays(3),
        ]);
    }

    // ── Access control ──────────────────────────────────────────────────────

    #[Test]
    public function operator_can_access_scan_page(): void
    {
        $this->actingAs($this->operator)
            ->get(route('ops.events.scan', $this->event))
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_access_scan_page(): void
    {
        $this->get(route('ops.events.scan', $this->event))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_cannot_access_operator_scan_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ops.events.scan', $this->event))
            ->assertForbidden();
    }

    // ── Mount ───────────────────────────────────────────────────────────────

    #[Test]
    public function component_auto_selects_single_session_on_mount(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventScan::class, ['event' => $this->event])
            ->assertSet('sessionId', $this->session->id);
    }

    #[Test]
    public function component_does_not_auto_select_when_multiple_sessions(): void
    {
        EventSession::factory()->for($this->event)->create();

        Livewire::actingAs($this->operator)
            ->test(OpsEventScan::class, ['event' => $this->event])
            ->assertSet('sessionId', null);
    }

    // ── QR scan ─────────────────────────────────────────────────────────────

    #[Test]
    public function valid_qr_sets_accepted_result(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventScan::class, ['event' => $this->event])
            ->call('processQrValue', 'itsk:att:v1:'.$this->rawToken)
            ->assertSet('resultOutcome', 'accepted');
    }

    #[Test]
    public function invalid_qr_sets_rejected_result(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventScan::class, ['event' => $this->event])
            ->call('processQrValue', 'invalid-qr-format')
            ->assertSet('resultOutcome', 'rejected');
    }

    #[Test]
    public function scan_result_added_to_recent_scans(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventScan::class, ['event' => $this->event])
            ->call('processQrValue', 'itsk:att:v1:'.$this->rawToken)
            ->assertCount('recentScans', 1);
    }

    #[Test]
    public function recent_scans_capped_at_eight(): void
    {
        $component = Livewire::actingAs($this->operator)
            ->test(OpsEventScan::class, ['event' => $this->event]);

        for ($i = 0; $i < 10; $i++) {
            $component->call('processQrValue', 'bad-qr-'.$i);
        }

        $component->assertCount('recentScans', 8);
    }

    #[Test]
    public function scan_ignored_when_no_session_selected(): void
    {
        $event = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);
        // Two sessions so it won't auto-select
        EventSession::factory()->for($event)->create();
        EventSession::factory()->for($event)->create();

        Livewire::actingAs($this->operator)
            ->test(OpsEventScan::class, ['event' => $event])
            ->assertSet('sessionId', null)
            ->call('processQrValue', 'itsk:att:v1:'.$this->rawToken)
            ->assertSet('resultOutcome', null);
    }

    #[Test]
    public function clear_result_resets_result_state(): void
    {
        Livewire::actingAs($this->operator)
            ->test(OpsEventScan::class, ['event' => $this->event])
            ->call('processQrValue', 'itsk:att:v1:'.$this->rawToken)
            ->assertSet('resultOutcome', 'accepted')
            ->call('clearResult')
            ->assertSet('resultOutcome', null)
            ->assertSet('resultMessage', '');
    }
}
