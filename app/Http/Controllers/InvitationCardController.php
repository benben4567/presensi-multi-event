<?php

namespace App\Http\Controllers;

use App\Enums\AccessStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PrintTemplate;
use App\Support\FpdfExtended;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvitationCardController extends Controller
{
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

        EventParticipant::query()
            ->with(['participant', 'invitation'])
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->where('event_participants.event_id', $event->id)
            ->where('event_participants.access_status', AccessStatus::Allowed->value)
            ->whereHas('invitation', fn ($q) => $q->whereNotNull('token')->whereNull('revoked_at'))
            ->select('event_participants.*')
            ->orderBy('participants.name')
            ->get()
            ->each(function ($ep) use ($pdf, $event, $template, &$count): void {
                $pdf->AddPage();

                if ($template) {
                    $this->addTemplateCard($pdf, $ep, $template);
                } else {
                    $this->addElegantCard($pdf, $event, $ep);
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
            return $this->printWithTemplate($event, $eventParticipant, $template);
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
    private function printWithTemplate(Event $event, EventParticipant $ep, PrintTemplate $template): Response
    {
        $pdf = new FpdfExtended('P', 'mm', [$template->page_width_mm, $template->page_height_mm]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $this->addTemplateCard($pdf, $ep, $template);

        $slug = str($ep->participant->name)->slug();
        $filename = "undangan-{$slug}.pdf";

        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    /**
     * Draw one template-based card onto an existing PDF instance.
     */
    private function addTemplateCard(FpdfExtended $pdf, EventParticipant $ep, PrintTemplate $template): void
    {
        $invitation = $ep->invitation;
        $bgPath = Storage::disk('public')->path($template->background_image_path);
        $ext = strtolower(pathinfo($bgPath, PATHINFO_EXTENSION));
        $fpdfType = $ext === 'jpg' || $ext === 'jpeg' ? 'JPEG' : 'PNG';

        // Background image fills the page
        $pdf->Image($bgPath, 0, 0, $template->page_width_mm, $template->page_height_mm, $fpdfType);

        // White quiet-zone backing box (2 mm padding)
        $padding = 2;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(
            $template->qr_x_mm - $padding,
            $template->qr_y_mm - $padding,
            $template->qr_w_mm + $padding * 2,
            $template->qr_h_mm + $padding * 2,
            'F',
        );

        // QR code
        $tmpFile = $this->generateQrPng('itsk:att:v1:'.$invitation->token);
        $pdf->Image($tmpFile, $template->qr_x_mm, $template->qr_y_mm, $template->qr_w_mm, $template->qr_h_mm, 'PNG');
        @unlink($tmpFile);

        // Nama peserta di bawah QR
        $encode = fn (string $text): string => iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
        $nameY = $template->qr_y_mm + $template->qr_h_mm + 1;
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($template->qr_x_mm - $padding, $nameY);
        $pdf->Cell($template->qr_w_mm + $padding * 2, 6, $encode($ep->participant->name), 0, 0, 'C');
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

            $this->addStickerLabel($pdf, $x, $y, $ep);

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

    /**
     * Draw one elegant portrait card (80mm × 105mm) onto the PDF instance.
     */
    private function addElegantCard(FpdfExtended $pdf, Event $event, EventParticipant $ep): void
    {
        $invitation = $ep->invitation;
        $encode = fn (string $text): string => iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
        $tmpFile = $this->generateQrPng('itsk:att:v1:'.$invitation->token);

        // ── Header strip (Y=0, H=13mm) ───────────────────────────────────────
        $pdf->SetFillColor(37, 99, 235);
        $pdf->Rect(0, 0, 80, 13, 'F');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(0, 0);
        $pdf->Cell(80, 13, 'PESERTA', 0, 0, 'C');

        // ── Event title ───────────────────────────────────────────────────────
        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY(5, 16);
        $pdf->MultiCell(70, 5.5, $encode($event->name), 0, 'C');
        $afterTitle = $pdf->GetY();

        // ── First divider ─────────────────────────────────────────────────────
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->SetLineWidth(0.25);
        $divY1 = $afterTitle + 1.5;
        $pdf->Line(8, $divY1, 72, $divY1);

        // ── QR code box ───────────────────────────────────────────────────────
        $qrBoxY = $divY1 + 2;
        $pdf->SetDrawColor(209, 213, 219);
        $pdf->SetLineWidth(0.4);
        $pdf->Rect(20.5, $qrBoxY, 39, 39);
        $pdf->Image($tmpFile, 23, $qrBoxY + 2.5, 34, 34, 'PNG');
        @unlink($tmpFile);

        // ── Second divider ────────────────────────────────────────────────────
        $divY2 = $qrBoxY + 39 + 2;
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->SetLineWidth(0.25);
        $pdf->Line(8, $divY2, 72, $divY2);

        // ── Participant name ──────────────────────────────────────────────────
        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(5, $divY2 + 2);
        $pdf->Cell(70, 5.5, $encode($ep->participant->name), 0, 0, 'C');
        $afterName = $divY2 + 2 + 5.5;

        // ── Phone (optional) ──────────────────────────────────────────────────
        $captionY = $afterName + 1.5;
        if ($ep->participant->phone_e164) {
            $pdf->SetTextColor(75, 85, 99);
            $pdf->SetFont('Courier', '', 9);
            $pdf->SetXY(5, $afterName + 1);
            $pdf->Cell(70, 4.5, $ep->participant->phone_e164, 0, 0, 'C');
            $captionY = $afterName + 1 + 4.5 + 1;
        }

        // ── Caption ───────────────────────────────────────────────────────────
        $pdf->SetTextColor(156, 163, 175);
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetXY(5, $captionY);
        $pdf->Cell(70, 4, 'Tunjukkan kartu ini saat check-in', 0, 0, 'C');

        // ── Footer strip (Y=95, H=10mm) ───────────────────────────────────────
        $pdf->SetFillColor(30, 58, 138);
        $pdf->Rect(0, 95, 80, 10, 'F');
        $pdf->SetFont('Arial', '', 6.5);
        $pdf->SetTextColor(200, 210, 230);
        $pdf->SetXY(0, 95);
        $pdf->Cell(80, 10, 'PRESENSI EVENT SYSTEM', 0, 0, 'C');
    }

    /**
     * Draw one sticker label (16mm × 31mm) with rounded border at position (x, y).
     */
    private function addStickerLabel(FpdfExtended $pdf, float $x, float $y, EventParticipant $ep): void
    {
        $invitation = $ep->invitation;
        $tmpFile = $this->generateQrPng('itsk:att:v1:'.$invitation->token, 300, 2);

        // Rounded border (1.5mm radius, light gray) — 16mm × 22mm
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);
        $pdf->roundedRect($x, $y, 16, 22, 1.5);

        // QR image: 14mm × 14mm, 1mm side margins, 1.5mm from top
        $pdf->Image($tmpFile, $x + 1, $y + 1.5, 14, 14, 'PNG');
        @unlink($tmpFile);

        // Invitation code: centered below QR
        $pdf->SetFont('Courier', '', 5);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetXY($x, $y + 16.5);
        $pdf->Cell(16, 4, (string) $invitation->invitation_code, 0, 0, 'C');
    }

    /**
     * Generate a QR PNG via GD (no imagick required).
     * Returns the path to the temp file; caller must delete it.
     */
    private function generateQrPng(string $content, int $pixelSize = 600, int $margin = 2): string
    {
        $qrCode = Encoder::encode($content, ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $matrixSize = $matrix->getWidth();

        $moduleSize = (int) floor($pixelSize / ($matrixSize + $margin * 2));
        $imgSize = ($matrixSize + $margin * 2) * $moduleSize;

        $img = imagecreatetruecolor($imgSize, $imgSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        for ($yy = 0; $yy < $matrixSize; $yy++) {
            for ($xx = 0; $xx < $matrixSize; $xx++) {
                if ($matrix->get($xx, $yy) === 1) {
                    $px = ($xx + $margin) * $moduleSize;
                    $py = ($yy + $margin) * $moduleSize;
                    imagefilledrectangle($img, $px, $py, $px + $moduleSize - 1, $py + $moduleSize - 1, $black);
                }
            }
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'qr_').'.png';
        imagepng($img, $tmpFile);
        imagedestroy($img);

        return $tmpFile;
    }
}
