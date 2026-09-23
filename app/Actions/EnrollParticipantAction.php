<?php

namespace App\Actions;

use App\Enums\AccessStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Invitation;
use App\Models\Participant;

class EnrollParticipantAction
{
    /**
     * Enroll a participant into an event and issue their QR invitation.
     */
    public function execute(Event $event, Participant $participant): EventParticipant
    {
        $enrollment = EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'access_status' => AccessStatus::Allowed->value,
        ]);

        $rawToken = bin2hex(random_bytes(32));

        Invitation::create([
            'event_participant_id' => $enrollment->id,
            'token_hash' => hash('sha256', $rawToken),
            'token' => $rawToken,
            'invitation_code' => Invitation::nextCodeForEvent($event),
            'issued_at' => now(),
            'expires_at' => $event->end_at,
        ]);

        return $enrollment;
    }
}
