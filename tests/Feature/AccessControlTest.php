<?php

namespace Tests\Feature;

use App\Actions\ImportPesertaAction;
use App\Actions\SetEnrollmentAccessAction;
use App\Enums\AccessStatus;
use App\Livewire\AdminEnrollmentList;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    private EventParticipant $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->event = Event::factory()->create();

        // Import one participant to get enrollment + invitation
        $csv = "nama,no_hp\nBudi Santoso,08123456789\n";
        $path = $this->writeTempCsv($csv);
        (new ImportPesertaAction)->execute($this->event, $path);

        $this->enrollment = EventParticipant::first();
    }

    // ── SetEnrollmentAccessAction ──────────────────────────────────────────

    #[Test]
    public function disable_sets_status_and_revokes_invitation(): void
    {
        $action = new SetEnrollmentAccessAction;
        $action->execute($this->enrollment, AccessStatus::Disabled, null, $this->admin->id);

        $this->enrollment->refresh();
        $this->assertEquals(AccessStatus::Disabled, $this->enrollment->access_status);

        $invitation = Invitation::first();
        $this->assertNotNull($invitation->revoked_at);
        $this->assertFalse($invitation->isValid());
    }

    #[Test]
    public function blacklist_sets_status_and_revokes_with_reason(): void
    {
        $action = new SetEnrollmentAccessAction;
        $action->execute($this->enrollment, AccessStatus::Blacklisted, 'Melanggar tata tertib', $this->admin->id);

        $this->enrollment->refresh();
        $this->assertEquals(AccessStatus::Blacklisted, $this->enrollment->access_status);
        $this->assertEquals('Melanggar tata tertib', $this->enrollment->access_reason);

        $invitation = Invitation::first();
        $this->assertNotNull($invitation->revoked_at);
        $this->assertEquals('Melanggar tata tertib', $invitation->revoked_reason);
        $this->assertEquals($this->admin->id, $invitation->revoked_by);
    }

    #[Test]
    public function enable_unrevokes_invitation(): void
    {
        $action = new SetEnrollmentAccessAction;

        // First blacklist
        $action->execute($this->enrollment, AccessStatus::Blacklisted, 'Alasan', $this->admin->id);
        $this->assertNotNull(Invitation::first()->revoked_at);

        // Then enable
        $action->execute($this->enrollment->fresh(), AccessStatus::Allowed, null, $this->admin->id);

        $this->enrollment->refresh();
        $this->assertEquals(AccessStatus::Allowed, $this->enrollment->access_status);
        $this->assertNull($this->enrollment->access_reason);

        $invitation = Invitation::first();
        $this->assertNull($invitation->revoked_at);
        $this->assertNull($invitation->revoked_reason);
        $this->assertTrue($invitation->isValid());
    }

    #[Test]
    public function disable_uses_default_revoke_reason(): void
    {
        (new SetEnrollmentAccessAction)->execute($this->enrollment, AccessStatus::Disabled, null, null);

        $invitation = Invitation::first();
        $this->assertEquals('Peserta dinonaktifkan', $invitation->revoked_reason);
    }

    #[Test]
    public function blacklist_reason_stored_on_enrollment(): void
    {
        (new SetEnrollmentAccessAction)->execute(
            $this->enrollment, AccessStatus::Blacklisted, 'Fraud', $this->admin->id
        );

        $this->assertDatabaseHas('event_participants', [
            'id' => $this->enrollment->id,
            'access_status' => 'blacklisted',
            'access_reason' => 'Fraud',
        ]);
    }

    #[Test]
    public function enable_clears_access_reason(): void
    {
        (new SetEnrollmentAccessAction)->execute(
            $this->enrollment, AccessStatus::Blacklisted, 'Alasan lama', $this->admin->id
        );

        (new SetEnrollmentAccessAction)->execute(
            $this->enrollment->fresh(), AccessStatus::Allowed, null, $this->admin->id
        );

        $this->assertDatabaseHas('event_participants', [
            'id' => $this->enrollment->id,
            'access_status' => 'allowed',
            'access_reason' => null,
        ]);
    }

    // ── AdminEnrollmentList component ──────────────────────────────────────

    #[Test]
    public function component_can_disable_via_event(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->dispatch('disable-enrollment', enrollmentId: $this->enrollment->id)
            ->assertDispatched('toast');

        $this->assertEquals(AccessStatus::Disabled, $this->enrollment->fresh()->access_status);
    }

    #[Test]
    public function component_can_enable_via_event(): void
    {
        // First disable
        (new SetEnrollmentAccessAction)->execute($this->enrollment, AccessStatus::Disabled, null, null);

        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->dispatch('enable-enrollment', enrollmentId: $this->enrollment->id)
            ->assertDispatched('toast');

        $this->assertEquals(AccessStatus::Allowed, $this->enrollment->fresh()->access_status);
    }

    #[Test]
    public function component_can_blacklist_with_valid_reason(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->call('openBlacklist', $this->enrollment->id)
            ->assertSet('showBlacklistForm', true)
            ->set('blacklistReason', 'Melanggar aturan')
            ->call('confirmBlacklist')
            ->assertHasNoErrors()
            ->assertSet('showBlacklistForm', false)
            ->assertDispatched('toast');

        $this->assertEquals(AccessStatus::Blacklisted, $this->enrollment->fresh()->access_status);
    }

    #[Test]
    public function component_blacklist_requires_reason(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->call('openBlacklist', $this->enrollment->id)
            ->set('blacklistReason', '')
            ->call('confirmBlacklist')
            ->assertHasErrors(['blacklistReason']);
    }

    #[Test]
    public function component_blacklist_reason_max_100_chars(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->call('openBlacklist', $this->enrollment->id)
            ->set('blacklistReason', str_repeat('x', 101))
            ->call('confirmBlacklist')
            ->assertHasErrors(['blacklistReason']);
    }

    #[Test]
    public function component_cancel_blacklist_hides_form(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminEnrollmentList::class, ['event' => $this->event])
            ->call('openBlacklist', $this->enrollment->id)
            ->assertSet('showBlacklistForm', true)
            ->call('cancelBlacklist')
            ->assertSet('showBlacklistForm', false)
            ->assertSet('pendingEnrollmentId', null);
    }

    // ── Helper ─────────────────────────────────────────────────────────────

    private function writeTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'access_test_').'.csv';
        file_put_contents($path, $content);

        return $path;
    }
}
