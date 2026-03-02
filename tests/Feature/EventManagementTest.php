<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Livewire\AdminEventForm;
use App\Livewire\AdminEventIndex;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    // ── Index page ─────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_view_event_index(): void
    {
        Event::factory()->count(3)->create();

        Livewire::actingAs($this->admin)
            ->test(AdminEventIndex::class)
            ->assertOk();
    }

    #[Test]
    public function event_index_shows_events(): void
    {
        $event = Event::factory()->create(['name' => 'Seminar Teknologi']);

        Livewire::actingAs($this->admin)
            ->test(AdminEventIndex::class)
            ->assertSee('Seminar Teknologi');
    }

    #[Test]
    public function event_index_search_filters_results(): void
    {
        Event::factory()->create(['name' => 'Workshop AI']);
        Event::factory()->create(['name' => 'Seminar Hukum']);

        Livewire::actingAs($this->admin)
            ->test(AdminEventIndex::class)
            ->set('search', 'Workshop')
            ->assertSee('Workshop AI')
            ->assertDontSee('Seminar Hukum');
    }

    #[Test]
    public function admin_can_delete_event(): void
    {
        $event = Event::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(AdminEventIndex::class)
            ->dispatch('delete-event', eventId: $event->id)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    // ── Create ─────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_event(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Seminar Nasional 2026')
            ->set('code', 'SN-2026')
            ->set('startAt', '2026-03-01T08:00')
            ->set('endAt', '2026-03-01T17:00')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('events', [
            'name' => 'Seminar Nasional 2026',
            'code' => 'SN-2026',
        ]);
    }

    #[Test]
    public function create_event_generates_sessions(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Event 3 Hari')
            ->set('startAt', '2026-03-01T08:00')
            ->set('endAt', '2026-03-03T17:00')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasNoErrors();

        $event = Event::where('name', 'Event 3 Hari')->first();
        $this->assertNotNull($event);
        $this->assertCount(3, $event->sessions);
    }

    #[Test]
    public function single_day_event_generates_one_session(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Event 1 Hari')
            ->set('startAt', '2026-05-10T08:00')
            ->set('endAt', '2026-05-10T17:00')
            ->set('status', 'draft')
            ->call('save');

        $event = Event::where('name', 'Event 1 Hari')->first();
        $this->assertCount(1, $event->sessions);
        $this->assertStringContainsString('2026', $event->sessions->first()->name);
    }

    #[Test]
    public function session_name_uses_indonesian_format(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Test Nama Hari')
            ->set('startAt', '2026-03-07T08:00') // Sabtu
            ->set('endAt', '2026-03-07T17:00')
            ->set('status', 'draft')
            ->call('save');

        $event = Event::where('name', 'Test Nama Hari')->first();
        $this->assertEquals('Sabtu, 7 Maret 2026', $event->sessions->first()->name);
    }

    #[Test]
    public function create_event_fails_without_required_fields(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', '')
            ->set('startAt', '')
            ->set('endAt', '')
            ->call('save')
            ->assertHasErrors(['name', 'startAt', 'endAt']);
    }

    #[Test]
    public function end_at_must_be_after_start_at(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Test')
            ->set('startAt', '2026-03-10T08:00')
            ->set('endAt', '2026-03-09T17:00')
            ->call('save')
            ->assertHasErrors(['endAt']);
    }

    #[Test]
    public function event_code_must_be_unique(): void
    {
        Event::factory()->create(['code' => 'KODE-123']);

        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Event Baru')
            ->set('code', 'KODE-123')
            ->set('startAt', '2026-04-01T08:00')
            ->set('endAt', '2026-04-01T17:00')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    #[Test]
    public function event_code_longer_than_10_characters_fails_validation(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Event Test')
            ->set('code', 'TOOLONGCODE1')  // 12 chars
            ->set('startAt', '2026-05-01T08:00')
            ->set('endAt', '2026-05-01T17:00')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    #[Test]
    public function event_code_of_exactly_10_characters_passes_validation(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Event Test')
            ->set('code', 'CODE123456')  // 10 chars
            ->set('startAt', '2026-05-01T08:00')
            ->set('endAt', '2026-05-01T17:00')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasNoErrors(['code']);
    }

    // ── Edit ───────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_edit_event(): void
    {
        $event = Event::factory()->create([
            'name' => 'Event Lama',
            'status' => EventStatus::Draft,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class, ['event' => $event])
            ->set('name', 'Event Diperbarui')
            ->set('status', 'open')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Event Diperbarui',
        ]);
    }

    #[Test]
    public function edit_event_code_unique_ignores_self(): void
    {
        $event = Event::factory()->create(['code' => 'MY-CODE']);

        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class, ['event' => $event])
            ->set('code', 'MY-CODE')
            ->call('save')
            ->assertHasNoErrors(['code']);
    }

    // ── Override ───────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_open_attendance_with_override(): void
    {
        $event = Event::factory()->create([
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDay(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class, ['event' => $event])
            ->call('openAttendance', 30)
            ->assertDispatched('toast');

        $this->assertNotNull($event->fresh()->override_until);
        $this->assertTrue($event->fresh()->isAttendanceOpen());
    }

    // ── Settings ───────────────────────────────────────────────────────────

    #[Test]
    public function operator_display_fields_saved_correctly(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEventForm::class)
            ->set('name', 'Event Dengan Setting')
            ->set('startAt', '2026-06-01T08:00')
            ->set('endAt', '2026-06-01T17:00')
            ->set('status', 'draft')
            ->set('operatorDisplayFields', ['name'])
            ->set('extraDisplayFields', 'unit, jabatan')
            ->set('enableCheckout', true)
            ->call('save')
            ->assertHasNoErrors();

        $event = Event::where('name', 'Event Dengan Setting')->first();
        $fields = $event->settings['operator_display_fields'];

        $this->assertContains('name', $fields);
        $this->assertContains('unit', $fields);
        $this->assertContains('jabatan', $fields);
        $this->assertTrue($event->settings['enable_checkout']);
    }
}
