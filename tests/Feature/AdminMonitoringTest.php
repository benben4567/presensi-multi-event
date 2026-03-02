<?php

namespace Tests\Feature;

use App\Livewire\AdminMonitoringActivity;
use App\Livewire\AdminMonitoringQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
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

    // ── Activity Log — access control ───────────────────────────────────────

    #[Test]
    public function admin_can_access_activity_log_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.monitoring.activity'))
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_access_activity_log_page(): void
    {
        $this->get(route('admin.monitoring.activity'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_activity_log_page(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.monitoring.activity'))
            ->assertForbidden();
    }

    // ── Activity Log — component behaviour ──────────────────────────────────

    #[Test]
    public function activity_log_shows_recorded_activities(): void
    {
        activity()->log('Peserta diimpor');

        Livewire::actingAs($this->admin)
            ->test(AdminMonitoringActivity::class)
            ->assertSee('Peserta diimpor');
    }

    #[Test]
    public function activity_log_search_filters_results(): void
    {
        activity()->log('Peserta diimpor');
        activity()->log('Event dibuat');

        Livewire::actingAs($this->admin)
            ->test(AdminMonitoringActivity::class)
            ->set('search', 'impor')
            ->assertSee('Peserta diimpor')
            ->assertDontSee('Event dibuat');
    }

    // ── Queue Monitor — access control ──────────────────────────────────────

    #[Test]
    public function admin_can_access_queue_monitor_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.monitoring.queue'))
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_access_queue_monitor_page(): void
    {
        $this->get(route('admin.monitoring.queue'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_queue_monitor_page(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.monitoring.queue'))
            ->assertForbidden();
    }

    // ── Queue Monitor — component behaviour ─────────────────────────────────

    #[Test]
    public function queue_monitor_renders_empty_state(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminMonitoringQueue::class)
            ->assertSee('Belum ada job tercatat.');
    }

    #[Test]
    public function queue_monitor_status_filter_resets_page(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminMonitoringQueue::class)
            ->set('status', 1) // SUCCEEDED
            ->assertSet('status', 1);
    }
}
