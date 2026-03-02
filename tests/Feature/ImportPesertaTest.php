<?php

namespace Tests\Feature;

use App\Actions\ImportPesertaAction;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Invitation;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportPesertaTest extends TestCase
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

    // ── ImportPesertaAction unit tests ─────────────────────────────────────

    #[Test]
    public function action_imports_valid_rows(): void
    {
        $csv = "nama,no_hp\nBudi Santoso,08123456789\nAni Rahayu,08987654321\n";
        $path = $this->writeTempCsv($csv);

        $action = new ImportPesertaAction;
        $result = $action->execute($this->event, $path);

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals(0, $result['errors']);
        $this->assertCount(2, Participant::all());
        $this->assertCount(2, EventParticipant::all());
        $this->assertCount(2, Invitation::all());
    }

    #[Test]
    public function action_normalizes_phone_to_e164(): void
    {
        $csv = "nama,no_hp\nBudi,08123456789\n";
        $path = $this->writeTempCsv($csv);

        (new ImportPesertaAction)->execute($this->event, $path);

        $this->assertDatabaseHas('participants', ['phone_e164' => '+628123456789']);
    }

    #[Test]
    public function action_skips_invalid_phone(): void
    {
        $csv = "nama,no_hp\nBudi,INVALID_PHONE\n";
        $path = $this->writeTempCsv($csv);

        $result = (new ImportPesertaAction)->execute($this->event, $path);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['errors']);
        $this->assertCount(1, $result['error_rows']);
        $this->assertStringContainsString('tidak valid', $result['error_rows'][0]['alasan']);
    }

    #[Test]
    public function action_skips_empty_phone(): void
    {
        $csv = "nama,no_hp\nBudi,\n";
        $path = $this->writeTempCsv($csv);

        $result = (new ImportPesertaAction)->execute($this->event, $path);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['errors']);
    }

    #[Test]
    public function action_skips_duplicate_in_same_event(): void
    {
        $csv = "nama,no_hp\nBudi,08123456789\nBudi Duplikat,08123456789\n";
        $path = $this->writeTempCsv($csv);

        $result = (new ImportPesertaAction)->execute($this->event, $path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertCount(1, EventParticipant::all());
    }

    #[Test]
    public function action_allows_same_participant_in_different_events(): void
    {
        $event2 = Event::factory()->create();
        $csv = "nama,no_hp\nBudi,08123456789\n";
        $path = $this->writeTempCsv($csv);

        (new ImportPesertaAction)->execute($this->event, $path);

        $path2 = $this->writeTempCsv($csv);
        $result2 = (new ImportPesertaAction)->execute($event2, $path2);

        $this->assertEquals(1, $result2['imported']);
        $this->assertCount(1, Participant::all());
        $this->assertCount(2, EventParticipant::all());
    }

    #[Test]
    public function action_creates_invitation_with_token(): void
    {
        $csv = "nama,no_hp\nBudi,08123456789\n";
        $path = $this->writeTempCsv($csv);

        (new ImportPesertaAction)->execute($this->event, $path);

        $invitation = Invitation::first();
        $this->assertNotNull($invitation);
        $this->assertNotNull($invitation->token);
        $this->assertNotNull($invitation->token_hash);
        $this->assertEquals(hash('sha256', $invitation->token), $invitation->token_hash);
        $this->assertEquals($this->event->end_at->toDateTimeString(), $invitation->expires_at->toDateTimeString());
    }

    #[Test]
    public function action_stores_extra_columns_as_meta(): void
    {
        $csv = "nama,no_hp,unit,jabatan\nBudi,08123456789,IT,Manager\n";
        $path = $this->writeTempCsv($csv);

        (new ImportPesertaAction)->execute($this->event, $path);

        $participant = Participant::first();
        $this->assertEquals('IT', $participant->meta['unit']);
        $this->assertEquals('Manager', $participant->meta['jabatan']);
    }

    #[Test]
    public function action_skips_row_with_empty_name(): void
    {
        $csv = "nama,no_hp\n,08123456789\n";
        $path = $this->writeTempCsv($csv);

        $result = (new ImportPesertaAction)->execute($this->event, $path);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['errors']);
    }

    // ── Livewire component ─────────────────────────────────────────────────

    #[Test]
    public function import_component_renders(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\AdminImportPeserta::class, ['event' => $this->event])
            ->assertOk();
    }

    #[Test]
    public function import_component_requires_file(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\AdminImportPeserta::class, ['event' => $this->event])
            ->call('import')
            ->assertHasErrors(['file']);
    }

    #[Test]
    public function import_component_shows_result_after_action(): void
    {
        // Test the action result shape that the component will display.
        $csv = "nama,no_hp\nBudi Santoso,08123456789\n";
        $path = $this->writeTempCsv($csv);
        $result = (new ImportPesertaAction)->execute($this->event, $path);

        $this->assertEquals(1, $result['imported']);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('error_rows', $result);
        $this->assertArrayHasKey('skipped_rows', $result);
    }

    // ── QR route ───────────────────────────────────────────────────────────

    #[Test]
    public function qr_route_returns_svg_for_valid_enrollment(): void
    {
        $csv = "nama,no_hp\nBudi,08123456789\n";
        $path = $this->writeTempCsv($csv);
        (new ImportPesertaAction)->execute($this->event, $path);

        $enrollment = EventParticipant::first();

        $this->actingAs($this->admin)
            ->get(route('admin.events.participants.qr', [$this->event, $enrollment]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    // ── Helper ─────────────────────────────────────────────────────────────

    private function writeTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_').'.csv';
        file_put_contents($path, $content);

        return $path;
    }
}
