<?php

namespace App\Jobs;

use App\Actions\BuildInvitationEmailPdfAction;
use App\Mail\InvitationCardMail;
use App\Models\EventParticipant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class SendInvitationCardEmailJob implements ShouldQueue
{
    use IsMonitored, Queueable;

    public int $tries = 3;

    public function __construct(public string $eventParticipantId) {}

    public function handle(BuildInvitationEmailPdfAction $builder): void
    {
        $ep = EventParticipant::with(['event', 'participant', 'invitation'])->find($this->eventParticipantId);

        if (! $ep || ! $ep->isAllowed() || ! $ep->participant->email) {
            return;
        }

        if (! $ep->invitation?->token || $ep->invitation->isRevoked()) {
            return;
        }

        $pdfBinary = $builder->execute($ep);
        $filename = 'undangan-'.str($ep->participant->name)->slug().'.pdf';

        Mail::to($ep->participant->email)->send(
            new InvitationCardMail($ep, $pdfBinary, $filename)
        );

        $ep->update(['invitation_email_sent_at' => now()]);
    }
}
