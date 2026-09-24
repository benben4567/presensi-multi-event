<?php

namespace Tests\Feature;

use App\Actions\BuildInvitationEmailPdfAction;
use App\Jobs\SendInvitationCardEmailJob;
use App\Mail\InvitationCardMail;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Invitation;
use App\Models\Participant;
use App\Support\FpdfExtended;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitationEmailTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private EventParticipant $ep;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->event = Event::factory()->create();
        $participant = Participant::factory()->create(['email' => 'peserta@example.com']);
        $this->ep = EventParticipant::factory()->for($this->event)->for($participant, 'participant')->create();
        Invitation::factory()->for($this->ep, 'eventParticipant')->create([
            'token' => \Illuminate\Support\Str::random(32),
        ]);
    }

    // ── BuildInvitationEmailPdfAction ────────────────────────────────────────

    #[Test]
    public function builds_single_page_pdf_without_static_info(): void
    {
        $pdf = app(BuildInvitationEmailPdfAction::class)->execute($this->ep->fresh(['event', 'participant', 'invitation']));

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, $this->countPdfPages($pdf));
    }

    #[Test]
    public function merges_static_info_pdf_pages_after_the_card(): void
    {
        $staticPath = $this->makeStaticPdfFixture(pages: 2);
        $this->event->update(['invitation_info_pdf_path' => $staticPath]);

        $pdf = app(BuildInvitationEmailPdfAction::class)->execute($this->ep->fresh(['event', 'participant', 'invitation']));

        $this->assertSame(3, $this->countPdfPages($pdf));
    }

    // ── SendInvitationCardEmailJob ────────────────────────────────────────────

    #[Test]
    public function job_sends_mail_and_marks_sent_at(): void
    {
        Mail::fake();

        (new SendInvitationCardEmailJob($this->ep->id))->handle(app(BuildInvitationEmailPdfAction::class));

        Mail::assertSent(InvitationCardMail::class, function (InvitationCardMail $mail) {
            return $mail->hasTo('peserta@example.com');
        });

        $this->assertNotNull($this->ep->fresh()->invitation_email_sent_at);
    }

    #[Test]
    public function job_skips_participant_without_email(): void
    {
        Mail::fake();

        $this->ep->participant->update(['email' => null]);

        (new SendInvitationCardEmailJob($this->ep->id))->handle(app(BuildInvitationEmailPdfAction::class));

        Mail::assertNothingSent();
        $this->assertNull($this->ep->fresh()->invitation_email_sent_at);
    }

    #[Test]
    public function job_skips_revoked_invitation(): void
    {
        Mail::fake();

        $this->ep->invitation->update(['revoked_at' => now()]);

        (new SendInvitationCardEmailJob($this->ep->id))->handle(app(BuildInvitationEmailPdfAction::class));

        Mail::assertNothingSent();
    }

    #[Test]
    public function job_skips_disabled_enrollment(): void
    {
        Mail::fake();

        $this->ep->update(['access_status' => 'disabled']);

        (new SendInvitationCardEmailJob($this->ep->id))->handle(app(BuildInvitationEmailPdfAction::class));

        Mail::assertNothingSent();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function countPdfPages(string $binary): int
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf_count_');
        file_put_contents($tmp, $binary);

        $reader = new FpdfExtended;
        $count = $reader->setSourceFile($tmp);
        @unlink($tmp);

        return $count;
    }

    private function makeStaticPdfFixture(int $pages): string
    {
        $pdf = new FpdfExtended('P', 'mm', [80, 105]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);

        for ($i = 0; $i < $pages; $i++) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetXY(5, 5);
            $pdf->Cell(70, 5, 'Info halaman '.($i + 1));
        }

        $relativePath = 'invitation-info-pdfs/test-fixture.pdf';
        Storage::disk('public')->put($relativePath, $pdf->Output('S'));

        return $relativePath;
    }
}
