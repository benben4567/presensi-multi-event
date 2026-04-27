<?php

namespace Tests\Feature;

use App\Actions\RecordAttendanceAction;
use App\Enums\AttendanceAction;
use App\Enums\ScanResultCode;
use App\Models\Device;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\Invitation;
use App\Models\ScanAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordAttendanceActionTest extends TestCase
{
    use RefreshDatabase;

    private RecordAttendanceAction $action;

    private Event $event;

    private EventSession $session;

    private EventParticipant $enrollment;

    private Invitation $invitation;

    private string $rawToken;

    private User $operator;

    private string $deviceUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new RecordAttendanceAction;
        $this->operator = User::factory()->create();
        $this->deviceUuid = 'device-uuid-test-001';

        $this->event = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);

        $this->session = EventSession::factory()->for($this->event)->create();

        $this->enrollment = EventParticipant::factory()->for($this->event)->create();

        $this->rawToken = bin2hex(random_bytes(32));

        $this->invitation = Invitation::factory()->for($this->enrollment, 'eventParticipant')->create([
            'token_hash' => hash('sha256', $this->rawToken),
            'token' => $this->rawToken,
            'issued_at' => now()->subHour(),
            'expires_at' => now()->addDays(3),
        ]);
    }

    // ── QR: happy paths ────────────────────────────────────────────────────

    #[Test]
    public function qr_check_in_accepted(): void
    {
        $result = $this->action->executeQr(
            $this->event,
            $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isAccepted());
        $this->assertEquals(ScanResultCode::CheckedIn, $result->code);
        $this->assertNotNull($result->log);

        $this->assertDatabaseHas('attendance_logs', [
            'event_participant_id' => $this->enrollment->id,
            'session_id' => $this->session->id,
            'action' => AttendanceAction::CheckIn->value,
        ]);

        $this->assertDatabaseHas('scan_attempts', [
            'event_id' => $this->event->id,
            'result' => 'accepted',
            'code' => ScanResultCode::CheckedIn->value,
        ]);
    }

    #[Test]
    public function qr_check_out_accepted_after_check_in(): void
    {
        // First scan → check-in
        $this->action->executeQr(
            $this->event,
            $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid,
            $this->operator->id,
        );

        // Second scan → check-out
        $result = $this->action->executeQr(
            $this->event,
            $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isAccepted());
        $this->assertEquals(ScanResultCode::CheckedOut, $result->code);

        $this->assertDatabaseHas('attendance_logs', [
            'event_participant_id' => $this->enrollment->id,
            'session_id' => $this->session->id,
            'action' => AttendanceAction::CheckOut->value,
        ]);
    }

    // ── QR: rejection paths ────────────────────────────────────────────────

    #[Test]
    public function qr_invalid_format_rejected(): void
    {
        $result = $this->action->executeQr(
            $this->event,
            $this->session,
            'invalid-qr-string',
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::TokenNotFound, $result->code);
        $this->assertNull($result->log);
        $this->assertDatabaseCount('attendance_logs', 0);
        $this->assertDatabaseCount('scan_attempts', 1);
    }

    #[Test]
    public function qr_token_not_found_rejected(): void
    {
        $result = $this->action->executeQr(
            $this->event,
            $this->session,
            'itsk:att:v1:nonexistent_token_xyz',
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::TokenNotFound, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function qr_revoked_token_rejected(): void
    {
        $this->invitation->update(['revoked_at' => now(), 'revoked_reason' => 'Test']);

        $result = $this->action->executeQr(
            $this->event,
            $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::TokenRevoked, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function qr_expired_token_rejected(): void
    {
        $this->invitation->update(['expires_at' => now()->subMinute()]);

        $result = $this->action->executeQr(
            $this->event,
            $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::TokenExpired, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function qr_event_mismatch_rejected(): void
    {
        $otherEvent = Event::factory()->open()->create([
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(3),
        ]);

        $result = $this->action->executeQr(
            $otherEvent,
            EventSession::factory()->for($otherEvent)->create(),
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::EventMismatch, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function qr_event_closed_rejected(): void
    {
        $closedEvent = Event::factory()->closed()->create();
        $enrollment = EventParticipant::factory()->for($closedEvent)->create();
        $token = bin2hex(random_bytes(32));

        Invitation::factory()->for($enrollment, 'eventParticipant')->create([
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'issued_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $result = $this->action->executeQr(
            $closedEvent,
            EventSession::factory()->for($closedEvent)->create(),
            'itsk:att:v1:'.$token,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::EventClosed, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function qr_event_draft_rejected(): void
    {
        $draftEvent = Event::factory()->create(); // default state is draft
        $enrollment = EventParticipant::factory()->for($draftEvent)->create();
        $token = bin2hex(random_bytes(32));

        Invitation::factory()->for($enrollment, 'eventParticipant')->create([
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'issued_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $result = $this->action->executeQr(
            $draftEvent,
            EventSession::factory()->for($draftEvent)->create(),
            'itsk:att:v1:'.$token,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::EventNotOpen, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function qr_participant_disabled_rejected(): void
    {
        $this->enrollment->update(['access_status' => 'disabled']);

        $result = $this->action->executeQr(
            $this->event,
            $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::ParticipantDisabled, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function qr_participant_blacklisted_rejected(): void
    {
        $this->enrollment->update(['access_status' => 'blacklisted']);

        $result = $this->action->executeQr(
            $this->event,
            $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::ParticipantBlacklisted, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    // ── QR: warning paths ─────────────────────────────────────────────────

    #[Test]
    public function qr_scan_after_full_cycle_gives_warning(): void
    {
        // Scan 1: check-in
        $this->action->executeQr(
            $this->event, $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid, $this->operator->id,
        );

        // Scan 2: check-out
        $this->action->executeQr(
            $this->event, $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid, $this->operator->id,
        );

        // Scan 3: both check_in and check_out already exist → warning
        $result = $this->action->executeQr(
            $this->event, $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid, $this->operator->id,
        );

        $this->assertTrue($result->isWarning());
        $this->assertEquals(ScanResultCode::DuplicateCheckOut, $result->code);
        $this->assertNull($result->log);
        $this->assertDatabaseCount('attendance_logs', 2);
    }

    // ── Manual: happy paths ────────────────────────────────────────────────

    #[Test]
    public function manual_check_in_accepted(): void
    {
        $result = $this->action->executeManual(
            $this->event,
            $this->session,
            $this->enrollment,
            AttendanceAction::CheckIn,
            $this->deviceUuid,
            $this->operator->id,
            'Masuk manual',
        );

        $this->assertTrue($result->isAccepted());
        $this->assertEquals(ScanResultCode::CheckedIn, $result->code);
        $this->assertNotNull($result->log);

        $this->assertDatabaseHas('attendance_logs', [
            'event_participant_id' => $this->enrollment->id,
            'action' => AttendanceAction::CheckIn->value,
        ]);

        $this->assertDatabaseHas('scan_attempts', [
            'source' => 'manual',
            'result' => 'accepted',
            'manual_note' => 'Masuk manual',
        ]);
    }

    #[Test]
    public function manual_check_out_accepted_after_check_in(): void
    {
        $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckIn, $this->deviceUuid, $this->operator->id,
        );

        $result = $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckOut, $this->deviceUuid, $this->operator->id,
        );

        $this->assertTrue($result->isAccepted());
        $this->assertEquals(ScanResultCode::CheckedOut, $result->code);
        $this->assertDatabaseCount('attendance_logs', 2);
    }

    // ── Manual: rejection paths ────────────────────────────────────────────

    #[Test]
    public function manual_event_closed_rejected(): void
    {
        $closedEvent = Event::factory()->closed()->create();
        $enrollment = EventParticipant::factory()->for($closedEvent)->create();

        $result = $this->action->executeManual(
            $closedEvent,
            EventSession::factory()->for($closedEvent)->create(),
            $enrollment,
            AttendanceAction::CheckIn,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::EventClosed, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function manual_event_draft_rejected(): void
    {
        $draftEvent = Event::factory()->create(); // default state is draft
        $enrollment = EventParticipant::factory()->for($draftEvent)->create();

        $result = $this->action->executeManual(
            $draftEvent,
            EventSession::factory()->for($draftEvent)->create(),
            $enrollment,
            AttendanceAction::CheckIn,
            $this->deviceUuid,
            $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::EventNotOpen, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function manual_participant_disabled_rejected(): void
    {
        $this->enrollment->update(['access_status' => 'disabled']);

        $result = $this->action->executeManual(
            $this->event, $this->session, $this->enrollment->fresh(),
            AttendanceAction::CheckIn, $this->deviceUuid, $this->operator->id,
        );

        $this->assertTrue($result->isRejected());
        $this->assertEquals(ScanResultCode::ParticipantDisabled, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    // ── Manual: warning paths ─────────────────────────────────────────────

    #[Test]
    public function manual_checkout_without_checkin_gives_warning(): void
    {
        $result = $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckOut, $this->deviceUuid, $this->operator->id,
        );

        $this->assertTrue($result->isWarning());
        $this->assertEquals(ScanResultCode::CheckoutWithoutCheckin, $result->code);
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    #[Test]
    public function manual_duplicate_check_in_gives_warning(): void
    {
        $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckIn, $this->deviceUuid, $this->operator->id,
        );

        $result = $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckIn, $this->deviceUuid, $this->operator->id,
        );

        $this->assertTrue($result->isWarning());
        $this->assertEquals(ScanResultCode::DuplicateCheckIn, $result->code);
        $this->assertDatabaseCount('attendance_logs', 1);
    }

    #[Test]
    public function manual_duplicate_check_out_gives_warning(): void
    {
        $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckIn, $this->deviceUuid, $this->operator->id,
        );
        $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckOut, $this->deviceUuid, $this->operator->id,
        );

        $result = $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckOut, $this->deviceUuid, $this->operator->id,
        );

        $this->assertTrue($result->isWarning());
        $this->assertEquals(ScanResultCode::DuplicateCheckOut, $result->code);
        $this->assertDatabaseCount('attendance_logs', 2);
    }

    // ── Invariants ─────────────────────────────────────────────────────────

    #[Test]
    public function every_scan_attempt_writes_scan_attempt_record(): void
    {
        // Rejected
        $this->action->executeQr($this->event, $this->session, 'bad', $this->deviceUuid, $this->operator->id);
        $this->assertDatabaseCount('scan_attempts', 1);

        // Accepted
        $this->action->executeQr(
            $this->event, $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid, $this->operator->id,
        );
        $this->assertDatabaseCount('scan_attempts', 2);

        // Warning (checkout without checkin)
        $this->action->executeManual(
            $this->event, $this->session, $this->enrollment,
            AttendanceAction::CheckOut, $this->deviceUuid, $this->operator->id,
        );
        $this->assertDatabaseCount('scan_attempts', 3);
    }

    #[Test]
    public function device_is_resolved_and_last_seen_updated_on_accepted_scan(): void
    {
        $this->assertDatabaseCount('devices', 0);

        $this->action->executeQr(
            $this->event, $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid, $this->operator->id,
        );

        $this->assertDatabaseCount('devices', 1);

        $device = Device::where('device_uuid', $this->deviceUuid)->first();
        $this->assertNotNull($device);
        $this->assertNotNull($device->last_seen_at);
    }

    #[Test]
    public function raw_token_is_never_stored_in_scan_attempts(): void
    {
        $this->action->executeQr(
            $this->event, $this->session,
            'itsk:att:v1:'.$this->rawToken,
            $this->deviceUuid, $this->operator->id,
        );

        $attempt = ScanAttempt::first();
        $this->assertNotEquals($this->rawToken, $attempt->token_fingerprint);
        $this->assertNotEquals('itsk:att:v1:'.$this->rawToken, $attempt->token_fingerprint);
    }
}
