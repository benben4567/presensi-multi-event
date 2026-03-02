<?php

namespace Tests\Feature;

use App\Actions\ImportPesertaAction;
use App\Livewire\AdminEnrollmentList;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnrollmentListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->event = Event::factory()->create();
    }

    #[Test]
    public function enrollment_list_renders(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->assertOk();
    }

    #[Test]
    public function enrollment_list_shows_participants(): void
    {
        $this->importCsv("nama,no_hp\nBudi Santoso,08123456789\n");

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->assertSee('Budi Santoso');
    }

    #[Test]
    public function enrollment_list_search_by_name(): void
    {
        $this->importCsv("nama,no_hp\nBudi Santoso,08123456789\nAni Rahayu,08987654321\n");

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->set('search', 'Budi')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Ani Rahayu');
    }

    #[Test]
    public function enrollment_list_shows_empty_state_when_no_participants(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->assertSee('Belum ada peserta terdaftar.');
    }

    #[Test]
    public function enrollment_list_route_accessible_to_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.events.participants', $this->event))
            ->assertOk();
    }

    #[Test]
    public function import_route_accessible_to_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.events.participants.import', $this->event))
            ->assertOk();
    }

    // ── Helper ─────────────────────────────────────────────────────────────

    private function importCsv(string $content): void
    {
        $path = tempnam(sys_get_temp_dir(), 'enroll_test_').'.csv';
        file_put_contents($path, $content);
        (new ImportPesertaAction)->execute($this->event, $path);
    }
}
