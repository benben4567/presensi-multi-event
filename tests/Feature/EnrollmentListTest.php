<?php

namespace Tests\Feature;

use App\Actions\ImportPesertaAction;
use App\Livewire\AdminEnrollmentList;
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

    #[Test]
    public function admin_can_add_a_single_participant(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->set('newName', 'Citra Dewi')
            ->set('newPhone', '08123456789')
            ->call('confirmAdd')
            ->assertHasNoErrors()
            ->assertSet('showAddForm', false);

        $participant = Participant::where('phone_e164', '+628123456789')->first();

        $this->assertNotNull($participant);
        $this->assertSame('Citra Dewi', $participant->name);

        $enrollment = EventParticipant::where('event_id', $this->event->id)
            ->where('participant_id', $participant->id)
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertTrue(Invitation::where('event_participant_id', $enrollment->id)->exists());
    }

    #[Test]
    public function admin_can_add_participant_with_custom_attributes(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->set('newName', 'Citra Dewi')
            ->set('newPhone', '08123456789')
            ->set('newMeta', [
                ['key' => 'Instansi', 'value' => 'ITSK'],
                ['key' => '', 'value' => 'diabaikan karena key kosong'],
            ])
            ->call('confirmAdd')
            ->assertHasNoErrors();

        $participant = Participant::where('phone_e164', '+628123456789')->first();

        $this->assertSame(['instansi' => 'ITSK'], $participant->meta);
    }

    #[Test]
    public function adding_participant_requires_name_and_phone(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->set('newName', '')
            ->set('newPhone', '')
            ->call('confirmAdd')
            ->assertHasErrors(['newName', 'newPhone']);
    }

    #[Test]
    public function adding_participant_rejects_invalid_phone(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->set('newName', 'Citra Dewi')
            ->set('newPhone', 'bukan-nomor')
            ->call('confirmAdd')
            ->assertHasErrors(['newPhone']);
    }

    #[Test]
    public function adding_participant_already_enrolled_shows_error(): void
    {
        $this->importCsv("nama,no_hp\nBudi Santoso,08123456789\n");

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->set('newName', 'Budi Santoso')
            ->set('newPhone', '08123456789')
            ->call('confirmAdd')
            ->assertHasErrors(['newPhone']);

        $this->assertSame(1, EventParticipant::where('event_id', $this->event->id)->count());
    }

    #[Test]
    public function adding_participant_reuses_existing_participant_in_other_event(): void
    {
        $otherEvent = Event::factory()->create();
        $participant = Participant::factory()->create(['name' => 'Budi Santoso', 'phone_e164' => '+628123456789']);
        $enrollmentOther = EventParticipant::create([
            'event_id' => $otherEvent->id,
            'participant_id' => $participant->id,
            'access_status' => 'allowed',
        ]);

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->set('newName', 'Budi Santoso')
            ->set('newPhone', '08123456789')
            ->call('confirmAdd')
            ->assertHasNoErrors();

        $this->assertSame(1, Participant::where('phone_e164', '+628123456789')->count());
        $this->assertTrue(EventParticipant::where('event_id', $this->event->id)->where('participant_id', $participant->id)->exists());
        $this->assertTrue(EventParticipant::where('id', $enrollmentOther->id)->exists());
    }

    #[Test]
    public function admin_can_edit_participant_name_and_phone(): void
    {
        $this->importCsv("nama,no_hp\nBudi Santoso,08123456789\n");
        $participant = Participant::where('phone_e164', '+628123456789')->first();
        $enrollment = EventParticipant::where('participant_id', $participant->id)->first();

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->call('openEditForm', $enrollment->id)
            ->assertSet('editName', 'Budi Santoso')
            ->assertSet('editPhone', '+628123456789')
            ->set('editName', 'Budi Santoso Updated')
            ->set('editPhone', '081298765432')
            ->call('confirmEdit')
            ->assertHasNoErrors()
            ->assertSet('showEditForm', false);

        $participant->refresh();
        $this->assertSame('Budi Santoso Updated', $participant->name);
        $this->assertSame('+6281298765432', $participant->phone_e164);
    }

    #[Test]
    public function editing_participant_phone_rejects_collision_with_another_participant(): void
    {
        $this->importCsv("nama,no_hp\nBudi Santoso,08123456789\nAni Rahayu,08987654321\n");
        $budi = Participant::where('phone_e164', '+628123456789')->first();
        $enrollment = EventParticipant::where('participant_id', $budi->id)->first();

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->call('openEditForm', $enrollment->id)
            ->set('editPhone', '08987654321')
            ->call('confirmEdit')
            ->assertHasErrors(['editPhone']);

        $this->assertSame('+628123456789', $budi->fresh()->phone_e164);
    }

    #[Test]
    public function editing_participant_does_not_affect_qr_invitation(): void
    {
        $this->importCsv("nama,no_hp\nBudi Santoso,08123456789\n");
        $participant = Participant::where('phone_e164', '+628123456789')->first();
        $enrollment = EventParticipant::where('participant_id', $participant->id)->first();
        $originalTokenHash = $enrollment->invitation->token_hash;

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->call('openEditForm', $enrollment->id)
            ->set('editPhone', '081298765432')
            ->call('confirmEdit')
            ->assertHasNoErrors();

        $this->assertSame($originalTokenHash, $enrollment->invitation->fresh()->token_hash);
    }

    #[Test]
    public function admin_cannot_disable_enrollment_from_another_event(): void
    {
        $otherEvent = Event::factory()->create();
        $otherEnrollment = EventParticipant::factory()->for($otherEvent)->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->dispatch('disable-enrollment', enrollmentId: $otherEnrollment->id);
    }

    #[Test]
    public function admin_cannot_edit_participant_from_another_event(): void
    {
        $otherEvent = Event::factory()->create();
        $otherEnrollment = EventParticipant::factory()->for($otherEvent)->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->call('openEditForm', $otherEnrollment->id);
    }

    // ── Helper ─────────────────────────────────────────────────────────────

    private function importCsv(string $content): void
    {
        $path = tempnam(sys_get_temp_dir(), 'enroll_test_').'.csv';
        file_put_contents($path, $content);
        (new ImportPesertaAction)->execute($this->event, $path);
    }
}
