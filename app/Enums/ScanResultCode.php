<?php

namespace App\Enums;

enum ScanResultCode: string
{
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case DuplicateCheckIn = 'duplicate_check_in';
    case DuplicateCheckOut = 'duplicate_check_out';
    case CheckoutWithoutCheckin = 'checkout_without_checkin';
    case TokenNotFound = 'token_not_found';
    case TokenExpired = 'token_expired';
    case TokenRevoked = 'token_revoked';
    case EventMismatch = 'event_mismatch';
    case EventNotOpen = 'event_not_open';
    case EventClosed = 'event_closed';
    case ParticipantDisabled = 'participant_disabled';
    case ParticipantBlacklisted = 'participant_blacklisted';

    public function label(): string
    {
        return match ($this) {
            self::CheckedIn => 'Check-in berhasil',
            self::CheckedOut => 'Check-out berhasil',
            self::DuplicateCheckIn => 'Duplikat check-in',
            self::DuplicateCheckOut => 'Duplikat check-out',
            self::CheckoutWithoutCheckin => 'Check-out tanpa check-in',
            self::TokenNotFound => 'Token tidak ditemukan',
            self::TokenExpired => 'Token kedaluwarsa',
            self::TokenRevoked => 'Token dicabut',
            self::EventMismatch => 'Event tidak cocok',
            self::EventNotOpen => 'Event belum dibuka',
            self::EventClosed => 'Event sudah ditutup',
            self::ParticipantDisabled => 'Peserta dinonaktifkan',
            self::ParticipantBlacklisted => 'Peserta diblacklist',
        };
    }
}
