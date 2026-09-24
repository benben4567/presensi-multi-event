<?php

namespace App\Http\Controllers;

use App\Enums\AccessStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PrintTemplate;
use App\Support\FpdfExtended;
use App\Support\InvitationCardRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Rap2hpoutre\FastExcel\FastExcel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvitationCardController extends Controller
{
    public function __construct(private readonly InvitationCardRenderer $renderer) {}

    // ── Elegant portrait invitation cards PDF ────────────────────────────────

    public function export(Event $event): Response|RedirectResponse
    {
        set_time_limit(300);

        $templateId = $event->settings['print_template_id'] ?? null;
        $template = $templateId ? PrintTemplate::find($templateId) : null;

        $pdf = $template
            ? new FpdfExtended('P', 'mm', [$template->page_width_mm, $template->page_height_mm])
            : new FpdfExtended('P', 'mm', [80, 105]);

        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);

        $count = 0;

        $captionField = $this->renderer->resolveCaptionField($event->settings['print_caption_field'] ?? 'name');

        EventParticipant::query()
            ->with(['participant', 'invitation'])
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->where('event_participants.event_id', $event->id)
            ->where('event_participants.access_status', AccessStatus::Allowed->value)
            ->whereHas('invitation', fn ($q) => $q->whereNotNull('token')->whereNull('revoked_at'))
            ->select('event_participants.*')
            ->orderBy('participants.name')
            ->get()
            ->each(function ($ep) use ($pdf, $event, $template, $captionField, &$count): void {
                $pdf->AddPage();

                if ($template) {
                    $this->renderer->renderTemplateCard($pdf, $ep, $template, $captionField);
                } else {
                    $this->renderer->renderElegantCard($pdf, $event, $ep);
                }

                $count++;
            });

        if ($count === 0) {
            return redirect()
                ->route('admin.events.participants', $event)
                ->with('error', 'Tidak ada peserta dengan undangan aktif untuk diekspor.');
        }

        $filename = 'undangan-'.str($event->code)->slug().'-'.now()->format('Ymd').'.pdf';

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    // ── Individual print — PDF with custom template or default HTML ─────────

    public function print(Event $event, EventParticipant $eventParticipant): Response
    {
        abort_if($eventParticipant->event_id !== $event->id, 404);

        $invitation = $eventParticipant->invitation;

        abort_if(! $invitation?->token, 404);
        abort_if($invitation->isRevoked(), 404);

        $templateId = $event->settings['print_template_id'] ?? null;

        if ($templateId && $template = PrintTemplate::find($templateId)) {
            return $this->printWithTemplate($event, $eventParticipant, $template, $event->settings['print_caption_field'] ?? 'name');
        }

        $qrSvg = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('M')
            ->generate('itsk:att:v1:'.$invitation->token);

        return response()->view('invitation-card', [
            'event' => $event,
            'participant' => $eventParticipant->participant,
            'qrSvg' => $qrSvg,
        ]);
    }

    /**
     * Render a custom-template PDF for one participant (individual print).
     */
    private function printWithTemplate(Event $event, EventParticipant $ep, PrintTemplate $template, string $rawCaptionField = 'name'): Response
    {
        $pdf = new FpdfExtended('P', 'mm', [$template->page_width_mm, $template->page_height_mm]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $this->renderer->renderTemplateCard($pdf, $ep, $template, $this->renderer->resolveCaptionField($rawCaptionField));

        $slug = str($ep->participant->name)->slug();
        $filename = "undangan-{$slug}.pdf";

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    // ── Sticker sheet PDF ────────────────────────────────────────────────────

    public function exportStickerPdf(Event $event): Response|RedirectResponse
    {
        set_time_limit(300);

        $enrollments = $this->activeStickerQuery($event)->get();

        if ($enrollments->isEmpty()) {
            return redirect()
                ->route('admin.events.participants', $event)
                ->with('error', 'Tidak ada peserta dengan kode undangan untuk diekspor ke stiker.');
        }

        // A4 layout constants (mm)
        $pageW = 210;
        $pageH = 297;
        $marginL = 10;
        $marginT = 15;
        $labelW = 16;
        $labelH = 22;
        $gapH = 3;   // horizontal gap (wider for visual breathing room)
        $gapV = 2;
        $cols = (int) floor(($pageW - 2 * $marginL + $gapH) / ($labelW + $gapH));
        $rows = (int) floor(($pageH - 2 * $marginT + $gapV) / ($labelH + $gapV));
        $perPage = $cols * $rows;

        $pdf = new FpdfExtended('P', 'mm', 'A4');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $position = 0;

        foreach ($enrollments as $ep) {
            if ($position > 0 && $position % $perPage === 0) {
                $pdf->AddPage();
            }

            $col = $position % $cols;
            $row = (int) floor(($position % $perPage) / $cols);

            $x = $marginL + $col * ($labelW + $gapH);
            $y = $marginT + $row * ($labelH + $gapV);

            $this->renderer->renderStickerLabel($pdf, $x, $y, $ep);

            $position++;
        }

        $filename = 'stiker-'.str($event->code)->slug().'-'.now()->format('Ymd').'.pdf';

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    // ── Sticker mapping CSV ──────────────────────────────────────────────────

    public function exportStickerCsv(Event $event): StreamedResponse|RedirectResponse
    {
        $enrollments = $this->activeStickerQuery($event)->get();

        if ($enrollments->isEmpty()) {
            return redirect()
                ->route('admin.events.participants', $event)
                ->with('error', 'Tidak ada peserta dengan kode undangan untuk diekspor ke CSV.');
        }

        $rows = $enrollments->map(function (EventParticipant $ep): array {
            $inv = $ep->invitation;

            return [
                'Kode Undangan' => $inv->invitation_code,
                'Nama' => $ep->participant->name,
                'No HP' => $ep->participant->phone_e164 ?? '',
                'Berlaku Hingga' => $inv->expires_at?->format('d/m/Y H:i'),
                'Status Akses' => $ep->access_status->value,
                'Dicabut Pada' => $inv->revoked_at?->format('d/m/Y H:i') ?? '',
                'Alasan Pencabutan' => $inv->revoked_reason ?? '',
            ];
        });

        $filename = 'mapping-stiker-'.str($event->code)->slug().'-'.now()->format('Ymd').'.csv';

        return (new FastExcel($rows))->download($filename);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Base query for sticker PDF and CSV: active, allowed, has invitation_code.
     *
     * @return \Illuminate\Database\Eloquent\Builder<EventParticipant>
     */
    private function activeStickerQuery(Event $event): \Illuminate\Database\Eloquent\Builder
    {
        return EventParticipant::query()
            ->with(['participant', 'invitation'])
            ->join('invitations', 'event_participants.id', '=', 'invitations.event_participant_id')
            ->where('event_participants.event_id', $event->id)
            ->where('event_participants.access_status', AccessStatus::Allowed->value)
            ->whereNotNull('invitations.invitation_code')
            ->whereNull('invitations.revoked_at')
            ->select('event_participants.*')
            ->orderBy('invitations.invitation_code');
    }
}
