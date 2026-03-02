<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvitationCardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private Event $event;

    private EventParticipant $ep;

    private Invitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator');

        $this->event = Event::factory()->create();

        $this->ep = EventParticipant::factory()->for($this->event)->create();

        $this->invitation = Invitation::factory()->for($this->ep, 'eventParticipant')->create([
            'token' => Str::random(32),
            'invitation_code' => 'TEST-0001',
        ]);
    }

    // ── Bulk export (elegant cards PDF) ──────────────────────────────────────

    #[Test]
    public function guest_cannot_access_bulk_export(): void
    {
        $this->get(route('admin.events.invitation-cards', $this->event))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_bulk_export(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.events.invitation-cards', $this->event))
            ->assertForbidden();
    }

    #[Test]
    public function admin_bulk_export_returns_pdf(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.events.invitation-cards', $this->event))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function bulk_export_skips_participants_without_invitation(): void
    {
        $epNoInvite = EventParticipant::factory()->for($this->event)->create();

        // Only $this->ep has an invitation — still returns a valid PDF
        $this->actingAs($this->admin)
            ->get(route('admin.events.invitation-cards', $this->event))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Confirm the participant without invitation was not the cause of a failure
        $this->assertNull($epNoInvite->fresh()->invitation);
    }

    #[Test]
    public function bulk_export_skips_revoked_invitations(): void
    {
        // Revoke the only invitation → 0 valid → redirect with error
        $this->invitation->update(['revoked_at' => now()]);

        $this->actingAs($this->admin)
            ->get(route('admin.events.invitation-cards', $this->event))
            ->assertRedirect(route('admin.events.participants', $this->event));
    }

    // ── Individual print ─────────────────────────────────────────────────────

    #[Test]
    public function guest_cannot_access_individual_print(): void
    {
        $this->get(route('admin.events.participants.card', [$this->event, $this->ep]))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_individual_print(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.events.participants.card', [$this->event, $this->ep]))
            ->assertForbidden();
    }

    #[Test]
    public function admin_individual_print_returns_html_card(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.events.participants.card', [$this->event, $this->ep]))
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee($this->ep->participant->name)
            ->assertSee('Tunjukkan kartu ini saat check-in');
    }

    #[Test]
    public function individual_print_returns_404_for_no_invitation(): void
    {
        $epNoInvite = EventParticipant::factory()->for($this->event)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.events.participants.card', [$this->event, $epNoInvite]))
            ->assertNotFound();
    }

    #[Test]
    public function individual_print_returns_404_for_revoked_invitation(): void
    {
        $this->invitation->update(['revoked_at' => now()]);

        $this->actingAs($this->admin)
            ->get(route('admin.events.participants.card', [$this->event, $this->ep]))
            ->assertNotFound();
    }

    #[Test]
    public function individual_print_returns_404_if_enrollment_not_in_event(): void
    {
        $otherEvent = Event::factory()->create();
        $otherEp = EventParticipant::factory()->for($otherEvent)->create();
        Invitation::factory()->for($otherEp, 'eventParticipant')->create([
            'token' => Str::random(32),
        ]);

        // Pass $otherEp but claim it belongs to $this->event
        $this->actingAs($this->admin)
            ->get(route('admin.events.participants.card', [$this->event, $otherEp]))
            ->assertNotFound();
    }

    // ── Sticker sheet PDF ─────────────────────────────────────────────────────

    #[Test]
    public function guest_cannot_access_sticker_pdf(): void
    {
        $this->get(route('admin.events.invitation-cards.sticker', $this->event))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_sticker_pdf(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.events.invitation-cards.sticker', $this->event))
            ->assertForbidden();
    }

    #[Test]
    public function admin_sticker_pdf_returns_pdf(): void
    {
        // $this->invitation already has invitation_code = 'TEST-25-0001'
        $this->actingAs($this->admin)
            ->get(route('admin.events.invitation-cards.sticker', $this->event))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function sticker_pdf_redirects_if_no_invitation_codes(): void
    {
        // Remove invitation_code from the only invitation
        $this->invitation->update(['invitation_code' => null]);

        $this->actingAs($this->admin)
            ->get(route('admin.events.invitation-cards.sticker', $this->event))
            ->assertRedirect(route('admin.events.participants', $this->event));
    }

    // ── Sticker mapping CSV ───────────────────────────────────────────────────

    #[Test]
    public function guest_cannot_access_sticker_csv(): void
    {
        $this->get(route('admin.events.invitation-cards.mapping', $this->event))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_sticker_csv(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.events.invitation-cards.mapping', $this->event))
            ->assertForbidden();
    }

    #[Test]
    public function admin_sticker_csv_returns_download(): void
    {
        // $this->invitation already has invitation_code = 'TEST-25-0001'
        $this->actingAs($this->admin)
            ->get(route('admin.events.invitation-cards.mapping', $this->event))
            ->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function sticker_csv_redirects_if_no_invitation_codes(): void
    {
        $this->invitation->update(['invitation_code' => null]);

        $this->actingAs($this->admin)
            ->get(route('admin.events.invitation-cards.mapping', $this->event))
            ->assertRedirect(route('admin.events.participants', $this->event));
    }

    // ── invitation_code generation ────────────────────────────────────────────

    #[Test]
    public function next_code_for_event_starts_at_0001(): void
    {
        $event = Event::factory()->create(['code' => 'CONF']);

        $this->assertSame('CONF-0001', Invitation::nextCodeForEvent($event));
    }

    #[Test]
    public function next_code_for_event_increments_sequentially(): void
    {
        $event = Event::factory()->create(['code' => 'CONF']);
        $ep1 = EventParticipant::factory()->for($event)->create();
        $ep2 = EventParticipant::factory()->for($event)->create();

        Invitation::factory()->for($ep1, 'eventParticipant')->create([
            'invitation_code' => 'CONF-0001',
        ]);

        $next = Invitation::nextCodeForEvent($event);

        $this->assertSame('CONF-0002', $next);

        Invitation::factory()->for($ep2, 'eventParticipant')->create([
            'invitation_code' => $next,
        ]);

        $this->assertSame('CONF-0003', Invitation::nextCodeForEvent($event));
    }

    #[Test]
    public function next_code_for_event_returns_null_when_event_has_no_code(): void
    {
        $event = Event::factory()->create(['code' => null]);

        $this->assertNull(Invitation::nextCodeForEvent($event));
    }

    #[Test]
    public function next_code_for_event_is_isolated_per_event(): void
    {
        $event1 = Event::factory()->create(['code' => 'EVT1']);
        $event2 = Event::factory()->create(['code' => 'EVT2']);

        $ep1 = EventParticipant::factory()->for($event1)->create();

        Invitation::factory()->for($ep1, 'eventParticipant')->create([
            'invitation_code' => 'EVT1-0003',
        ]);

        // event2 starts independently at 0001
        $this->assertSame('EVT2-0001', Invitation::nextCodeForEvent($event2));
    }
}
