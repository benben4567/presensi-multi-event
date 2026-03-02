<?php

namespace Database\Factories;

use App\Enums\ScanResultCode;
use App\Enums\ScanSource;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScanAttempt>
 */
class ScanAttemptFactory extends Factory
{
    public function definition(): array
    {
        $event = Event::factory()->create();
        $session = EventSession::factory()->for($event)->create();
        $enrollment = EventParticipant::factory()->for($event)->create();

        return [
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'device_uuid' => Str::uuid()->toString(),
            'operator_user_id' => User::factory(),
            'source' => ScanSource::Qr,
            'result' => 'accepted',
            'code' => ScanResultCode::CheckedIn->value,
            'message' => 'Berhasil: Presensi berhasil dicatat.',
            'token_fingerprint' => hash('sha256', Str::random(32)),
            'manual_note' => null,
            'scanned_at' => now(),
        ];
    }

    public function rejected(): static
    {
        return $this->state([
            'result' => 'rejected',
            'code' => ScanResultCode::TokenExpired->value,
            'message' => 'Ditolak: Kode QR sudah kedaluwarsa.',
        ]);
    }

    public function warning(): static
    {
        return $this->state([
            'result' => 'warning',
            'code' => ScanResultCode::DuplicateCheckIn->value,
            'message' => 'Duplikat: Peserta sudah melakukan presensi pada hari ini.',
        ]);
    }

    public function manual(): static
    {
        return $this->state([
            'source' => ScanSource::Manual,
            'token_fingerprint' => null,
        ]);
    }
}
