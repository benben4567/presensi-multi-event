<?php

namespace App\Actions;

use App\Enums\AccessStatus;
use App\Models\EventParticipant;

class SetEnrollmentAccessAction
{
    /**
     * Change the access status of an enrollment and synchronise the invitation revocation.
     *
     * Rules:
     * - allowed      → unrevoke invitation (clear revoked_at / reason / by)
     * - disabled     → revoke invitation (reason optional)
     * - blacklisted  → revoke invitation (reason required, caller must validate)
     */
    public function execute(
        EventParticipant $enrollment,
        AccessStatus $status,
        ?string $reason = null,
        ?string $updatedBy = null,
    ): void {
        $enrollment->update([
            'access_status' => $status->value,
            'access_reason' => $reason,
            'access_updated_at' => now(),
            'access_updated_by' => $updatedBy,
        ]);

        if ($status === AccessStatus::Allowed) {
            $enrollment->invitation?->update([
                'revoked_at' => null,
                'revoked_reason' => null,
                'revoked_by' => null,
            ]);
        } else {
            $defaultReason = match ($status) {
                AccessStatus::Disabled => 'Peserta dinonaktifkan',
                AccessStatus::Blacklisted => 'Peserta diblacklist',
                default => 'Akses dicabut',
            };

            $enrollment->invitation?->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason ?? $defaultReason,
                'revoked_by' => $updatedBy,
            ]);
        }
    }
}
