<?php

namespace App\Actions;

use App\Enums\AccessStatus;
use App\Enums\AttendanceAction;
use App\Enums\EventStatus;
use App\Enums\ScanResultCode;
use App\Enums\ScanSource;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\Invitation;
use App\Models\ScanAttempt;

class RecordAttendanceAction
{
    private const QR_PREFIX = 'itsk:att:v1:';

    /**
     * Process a QR code scan.
     * Expected token format: itsk:att:v1:<rawToken>
     */
    public function executeQr(
        Event $event,
        EventSession $session,
        string $rawToken,
        string $deviceUuid,
        string $operatorUserId,
    ): AttendanceScanResult {
        if (! str_starts_with($rawToken, self::QR_PREFIX)) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                source: ScanSource::Qr,
                code: ScanResultCode::TokenNotFound,
                message: 'Format QR tidak valid',
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
            );

            return $this->rejected(ScanResultCode::TokenNotFound, 'Format QR tidak valid');
        }

        $token = substr($rawToken, strlen(self::QR_PREFIX));
        $tokenHash = hash('sha256', $token);
        $tokenFingerprint = substr($tokenHash, 0, 16);

        $invitation = Invitation::with('eventParticipant')
            ->where('token_hash', $tokenHash)
            ->first();

        if ($invitation === null) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                source: ScanSource::Qr,
                code: ScanResultCode::TokenNotFound,
                message: 'QR tidak ditemukan',
                tokenFingerprint: $tokenFingerprint,
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
            );

            return $this->rejected(ScanResultCode::TokenNotFound, 'QR tidak ditemukan');
        }

        $enrollment = $invitation->eventParticipant;

        if ($enrollment->event_id !== $event->id) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Qr,
                code: ScanResultCode::EventMismatch,
                message: 'QR bukan untuk event ini',
                tokenFingerprint: $tokenFingerprint,
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
            );

            return $this->rejected(ScanResultCode::EventMismatch, 'QR bukan untuk event ini');
        }

        if ($invitation->isRevoked()) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Qr,
                code: ScanResultCode::TokenRevoked,
                message: 'QR telah dicabut',
                tokenFingerprint: $tokenFingerprint,
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
            );

            return $this->rejected(ScanResultCode::TokenRevoked, 'QR telah dicabut');
        }

        if ($invitation->isExpired()) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Qr,
                code: ScanResultCode::TokenExpired,
                message: 'QR telah kedaluwarsa',
                tokenFingerprint: $tokenFingerprint,
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
            );

            return $this->rejected(ScanResultCode::TokenExpired, 'QR telah kedaluwarsa');
        }

        $eventError = $this->getEventStatusError($event);

        if ($eventError !== null) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Qr,
                code: $eventError['code'],
                message: $eventError['message'],
                tokenFingerprint: $tokenFingerprint,
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
            );

            return $this->rejected($eventError['code'], $eventError['message']);
        }

        $accessDenial = $this->checkAccess($enrollment);

        if ($accessDenial !== null) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Qr,
                code: $accessDenial['code'],
                message: $accessDenial['message'],
                tokenFingerprint: $tokenFingerprint,
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
            );

            return $this->rejected($accessDenial['code'], $accessDenial['message']);
        }

        [$action, $warningCode] = $this->determineAction($enrollment->id, $session->id);

        if ($warningCode !== null) {
            $message = $this->warningMessage($warningCode);

            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Qr,
                code: $warningCode,
                message: $message,
                tokenFingerprint: $tokenFingerprint,
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
            );

            return $this->warning($warningCode, $message);
        }

        $device = $this->resolveDevice($deviceUuid);
        $log = $this->writeAttendanceLog($event, $enrollment, $session, $action, $device->id, $operatorUserId);

        $resultCode = $action === AttendanceAction::CheckIn ? ScanResultCode::CheckedIn : ScanResultCode::CheckedOut;
        $message = $action === AttendanceAction::CheckIn ? 'Check-in berhasil' : 'Check-out berhasil';

        $this->writeScanAttempt(
            eventId: $event->id,
            sessionId: $session->id,
            eventParticipantId: $enrollment->id,
            source: ScanSource::Qr,
            code: $resultCode,
            message: $message,
            tokenFingerprint: $tokenFingerprint,
            deviceUuid: $deviceUuid,
            operatorUserId: $operatorUserId,
        );

        return $this->accepted($resultCode, $message, $log);
    }

    /**
     * Process a manual attendance entry by an operator.
     */
    public function executeManual(
        Event $event,
        EventSession $session,
        EventParticipant $enrollment,
        AttendanceAction $action,
        string $deviceUuid,
        string $operatorUserId,
        ?string $manualNote = null,
    ): AttendanceScanResult {
        $eventError = $this->getEventStatusError($event);

        if ($eventError !== null) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Manual,
                code: $eventError['code'],
                message: $eventError['message'],
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
                manualNote: $manualNote,
            );

            return $this->rejected($eventError['code'], $eventError['message']);
        }

        $accessDenial = $this->checkAccess($enrollment);

        if ($accessDenial !== null) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Manual,
                code: $accessDenial['code'],
                message: $accessDenial['message'],
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
                manualNote: $manualNote,
            );

            return $this->rejected($accessDenial['code'], $accessDenial['message']);
        }

        $existingCheckIn = AttendanceLog::where('event_participant_id', $enrollment->id)
            ->where('session_id', $session->id)
            ->where('action', AttendanceAction::CheckIn->value)
            ->exists();

        $existingCheckOut = AttendanceLog::where('event_participant_id', $enrollment->id)
            ->where('session_id', $session->id)
            ->where('action', AttendanceAction::CheckOut->value)
            ->exists();

        if ($action === AttendanceAction::CheckIn && $existingCheckIn) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Manual,
                code: ScanResultCode::DuplicateCheckIn,
                message: 'Peserta sudah check-in',
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
                manualNote: $manualNote,
            );

            return $this->warning(ScanResultCode::DuplicateCheckIn, 'Peserta sudah check-in');
        }

        if ($action === AttendanceAction::CheckOut && ! $existingCheckIn) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Manual,
                code: ScanResultCode::CheckoutWithoutCheckin,
                message: 'Peserta belum check-in',
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
                manualNote: $manualNote,
            );

            return $this->warning(ScanResultCode::CheckoutWithoutCheckin, 'Peserta belum check-in');
        }

        if ($action === AttendanceAction::CheckOut && $existingCheckOut) {
            $this->writeScanAttempt(
                eventId: $event->id,
                sessionId: $session->id,
                eventParticipantId: $enrollment->id,
                source: ScanSource::Manual,
                code: ScanResultCode::DuplicateCheckOut,
                message: 'Peserta sudah check-out',
                deviceUuid: $deviceUuid,
                operatorUserId: $operatorUserId,
                manualNote: $manualNote,
            );

            return $this->warning(ScanResultCode::DuplicateCheckOut, 'Peserta sudah check-out');
        }

        $device = $this->resolveDevice($deviceUuid);
        $log = $this->writeAttendanceLog($event, $enrollment, $session, $action, $device->id, $operatorUserId);

        $resultCode = $action === AttendanceAction::CheckIn ? ScanResultCode::CheckedIn : ScanResultCode::CheckedOut;
        $message = $action === AttendanceAction::CheckIn ? 'Check-in berhasil' : 'Check-out berhasil';

        $this->writeScanAttempt(
            eventId: $event->id,
            sessionId: $session->id,
            eventParticipantId: $enrollment->id,
            source: ScanSource::Manual,
            code: $resultCode,
            message: $message,
            deviceUuid: $deviceUuid,
            operatorUserId: $operatorUserId,
            manualNote: $manualNote,
        );

        return $this->accepted($resultCode, $message, $log);
    }

    /**
     * @return array{code: ScanResultCode, message: string}|null
     */
    private function getEventStatusError(Event $event): ?array
    {
        if ($event->status === EventStatus::Draft) {
            return ['code' => ScanResultCode::EventNotOpen, 'message' => 'Event belum dibuka'];
        }

        if ($event->status === EventStatus::Closed || ! $event->isAttendanceOpen()) {
            return ['code' => ScanResultCode::EventClosed, 'message' => 'Event sudah ditutup'];
        }

        return null;
    }

    /**
     * @return array{code: ScanResultCode, message: string}|null
     */
    private function checkAccess(EventParticipant $enrollment): ?array
    {
        return match ($enrollment->access_status) {
            AccessStatus::Disabled => ['code' => ScanResultCode::ParticipantDisabled, 'message' => 'Peserta dinonaktifkan'],
            AccessStatus::Blacklisted => ['code' => ScanResultCode::ParticipantBlacklisted, 'message' => 'Peserta diblacklist'],
            default => null,
        };
    }

    /**
     * Determine the next attendance action based on existing logs.
     * Returns [action, warningCode] — warningCode is non-null when attendance is already complete.
     *
     * @return array{AttendanceAction|null, ScanResultCode|null}
     */
    private function determineAction(int $enrollmentId, int $sessionId): array
    {
        $hasCheckIn = AttendanceLog::where('event_participant_id', $enrollmentId)
            ->where('session_id', $sessionId)
            ->where('action', AttendanceAction::CheckIn->value)
            ->exists();

        $hasCheckOut = AttendanceLog::where('event_participant_id', $enrollmentId)
            ->where('session_id', $sessionId)
            ->where('action', AttendanceAction::CheckOut->value)
            ->exists();

        if (! $hasCheckIn) {
            return [AttendanceAction::CheckIn, null];
        }

        if (! $hasCheckOut) {
            return [AttendanceAction::CheckOut, null];
        }

        return [null, ScanResultCode::DuplicateCheckOut];
    }

    private function warningMessage(ScanResultCode $code): string
    {
        return match ($code) {
            ScanResultCode::DuplicateCheckIn => 'Peserta sudah check-in',
            ScanResultCode::DuplicateCheckOut => 'Peserta sudah check-out',
            ScanResultCode::CheckoutWithoutCheckin => 'Peserta belum check-in',
            default => 'Duplikat presensi',
        };
    }

    private function resolveDevice(string $deviceUuid): Device
    {
        $device = Device::firstOrCreate(
            ['device_uuid' => $deviceUuid],
            ['name' => $deviceUuid, 'last_seen_at' => now()],
        );

        $device->update(['last_seen_at' => now()]);

        return $device;
    }

    private function writeAttendanceLog(
        Event $event,
        EventParticipant $enrollment,
        EventSession $session,
        AttendanceAction $action,
        int $deviceId,
        string $operatorUserId,
    ): AttendanceLog {
        return AttendanceLog::create([
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'action' => $action,
            'scanned_at' => now(),
            'device_id' => $deviceId,
            'operator_user_id' => $operatorUserId,
        ]);
    }

    private function writeScanAttempt(
        string $eventId,
        ?int $sessionId,
        ScanSource $source,
        ScanResultCode $code,
        string $message,
        ?int $eventParticipantId = null,
        ?string $tokenFingerprint = null,
        ?string $deviceUuid = null,
        ?string $operatorUserId = null,
        ?string $manualNote = null,
    ): void {
        $outcome = match (true) {
            in_array($code, [ScanResultCode::CheckedIn, ScanResultCode::CheckedOut], true) => 'accepted',
            in_array($code, [ScanResultCode::DuplicateCheckIn, ScanResultCode::DuplicateCheckOut, ScanResultCode::CheckoutWithoutCheckin], true) => 'warning',
            default => 'rejected',
        };

        ScanAttempt::create([
            'event_id' => $eventId,
            'event_participant_id' => $eventParticipantId,
            'session_id' => $sessionId,
            'device_uuid' => $deviceUuid,
            'operator_user_id' => $operatorUserId,
            'source' => $source,
            'result' => $outcome,
            'code' => $code,
            'message' => $message,
            'token_fingerprint' => $tokenFingerprint,
            'manual_note' => $manualNote,
            'scanned_at' => now(),
        ]);
    }

    private function accepted(ScanResultCode $code, string $message, ?AttendanceLog $log = null): AttendanceScanResult
    {
        return new AttendanceScanResult('accepted', $code, $message, $log);
    }

    private function warning(ScanResultCode $code, string $message): AttendanceScanResult
    {
        return new AttendanceScanResult('warning', $code, $message);
    }

    private function rejected(ScanResultCode $code, string $message): AttendanceScanResult
    {
        return new AttendanceScanResult('rejected', $code, $message);
    }
}
