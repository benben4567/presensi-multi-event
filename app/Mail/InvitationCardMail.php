<?php

namespace App\Mail;

use App\Models\EventParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationCardMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventParticipant $eventParticipant,
        public string $pdfBinary,
        public string $filename,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Undangan '.$this->eventParticipant->event->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation-card',
            with: [
                'eventName' => $this->eventParticipant->event->name,
                'participantName' => $this->eventParticipant->participant->name,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
