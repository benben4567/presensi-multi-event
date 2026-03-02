<?php

namespace Tests\Feature;

use App\Enums\AccessStatus;
use App\Enums\AttendanceAction;
use App\Enums\ScanResultCode;
use App\Enums\ScanSource;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\Invitation;
use App\Models\Participant;
use App\Models\ScanAttempt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function event_has_many_sessions(): void
    {
        $event = Event::factory()->create();
        EventSession::factory()->for($event)->count(3)->create();

        $this->assertCount(3, $event->sessions);
        $this->assertInstanceOf(EventSession::class, $event->sessions->first());
    }

    #[Test]
    public function event_has_many_event_participants(): void
    {
        $event = Event::factory()->create();
        EventParticipant::factory()->for($event)->count(2)->create();

        $this->assertCount(2, $event->eventParticipants);
    }

    #[Test]
    public function event_participant_belongs_to_event_and_participant(): void
    {
        $enrollment = EventParticipant::factory()->create();

        $this->assertInstanceOf(Event::class, $enrollment->event);
        $this->assertInstanceOf(Participant::class, $enrollment->participant);
    }

    #[Test]
    public function event_participant_has_one_invitation(): void
    {
        $enrollment = EventParticipant::factory()->create();
        Invitation::factory()->for($enrollment)->create();

        $this->assertInstanceOf(Invitation::class, $enrollment->invitation);
    }

    #[Test]
    public function invitation_is_unique_per_event_participant(): void
    {
        $enrollment = EventParticipant::factory()->create();
        Invitation::factory()->for($enrollment)->create();

        $this->expectException(QueryException::class);

        Invitation::factory()->for($enrollment)->create();
    }

    #[Test]
    public function event_participant_unique_per_event(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->create();
        EventParticipant::factory()->for($event)->for($participant)->create();

        $this->expectException(QueryException::class);

        EventParticipant::factory()->for($event)->for($participant)->create();
    }

    #[Test]
    public function attendance_log_enforces_dedupe_unique_constraint(): void
    {
        $event = Event::factory()->create();
        $session = EventSession::factory()->for($event)->create();
        $enrollment = EventParticipant::factory()->for($event)->create();
        $device = Device::factory()->create();
        $operator = User::factory()->create();

        AttendanceLog::create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'action' => AttendanceAction::CheckIn,
            'scanned_at' => now(),
            'device_id' => $device->id,
            'operator_user_id' => $operator->id,
        ]);

        $this->expectException(QueryException::class);

        AttendanceLog::create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'action' => AttendanceAction::CheckIn,
            'scanned_at' => now(),
            'device_id' => $device->id,
            'operator_user_id' => $operator->id,
        ]);
    }

    #[Test]
    public function invitation_is_revoked_and_expired_helpers_work(): void
    {
        $active = Invitation::factory()->create([
            'expires_at' => now()->addDays(5),
            'revoked_at' => null,
        ]);

        $revoked = Invitation::factory()->create([
            'expires_at' => now()->addDays(5),
            'revoked_at' => now(),
        ]);

        $expired = Invitation::factory()->create([
            'expires_at' => now()->subDay(),
            'revoked_at' => null,
        ]);

        $this->assertTrue($active->isValid());
        $this->assertFalse($revoked->isValid());
        $this->assertFalse($expired->isValid());
    }

    #[Test]
    public function access_status_enum_cast_works(): void
    {
        $enrollment = EventParticipant::factory()->disabled()->create();
        $this->assertSame(AccessStatus::Disabled, $enrollment->access_status);
        $this->assertFalse($enrollment->isAllowed());
    }

    #[Test]
    public function event_is_attendance_open_with_override(): void
    {
        $event = Event::factory()->closed()->create([
            'override_until' => now()->addMinutes(30),
        ]);

        $this->assertTrue($event->isAttendanceOpen());

        $event->update(['override_until' => now()->subMinute()]);
        $this->assertFalse($event->fresh()->isAttendanceOpen());
    }

    #[Test]
    public function scan_attempt_enums_are_cast_correctly(): void
    {
        $event = Event::factory()->create();
        $session = EventSession::factory()->for($event)->create();
        $enrollment = EventParticipant::factory()->for($event)->create();

        $attempt = ScanAttempt::create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'device_uuid' => 'test-uuid',
            'source' => ScanSource::Qr,
            'result' => 'accepted',
            'code' => ScanResultCode::CheckedIn->value,
            'message' => 'Berhasil: Presensi berhasil dicatat.',
            'scanned_at' => now(),
        ]);

        $this->assertSame(ScanSource::Qr, $attempt->fresh()->source);
        $this->assertSame(ScanResultCode::CheckedIn, $attempt->fresh()->code);
    }
}
