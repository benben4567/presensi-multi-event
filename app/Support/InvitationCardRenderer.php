<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\PrintTemplate;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Storage;

/**
 * Draws one participant's invitation card onto an existing FPDF page.
 *
 * Shared between InvitationCardController (bulk/individual PDF export) and
 * the per-participant email send job, so the card layout only lives once.
 */
class InvitationCardRenderer
{
    /**
     * Draw one elegant portrait card (80mm × 105mm) onto the PDF instance.
     */
    public function renderElegantCard(FpdfExtended $pdf, Event $event, EventParticipant $ep): void
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
     * Draw one template-based card onto an existing PDF instance.
     *
     * @param  string  $captionField  Resolved caption field: 'name'|'invitation_code'|'phone'|'none'|'meta.{key}'
     */
    public function renderTemplateCard(FpdfExtended $pdf, EventParticipant $ep, PrintTemplate $template, string $captionField = 'name'): void
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

        $caption = $this->resolveCaption($captionField, $ep);

        if ($caption !== null && $caption !== '') {
            $encode = fn (string $text): string => iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
            $captionY = $template->qr_y_mm + $template->qr_h_mm + 1;
            $pdf->SetFont('Courier', 'B', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetXY($template->qr_x_mm - $padding, $captionY);
            $pdf->Cell($template->qr_w_mm + $padding * 2, 6, $encode($caption), 0, 0, 'C', true);
        }
    }

    /**
     * Draw one sticker label (16mm × 22mm) with rounded border at position (x, y).
     */
    public function renderStickerLabel(FpdfExtended $pdf, float $x, float $y, EventParticipant $ep): void
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
     * Normalize the raw caption field value from settings, defaulting unknown values to 'name'.
     */
    public function resolveCaptionField(string $raw): string
    {
        $known = ['name', 'invitation_code', 'phone', 'none'];

        if (in_array($raw, $known, true) || str_starts_with($raw, 'meta.')) {
            return $raw;
        }

        return 'name';
    }

    /**
     * Resolve the caption string for a participant given a caption field key.
     */
    public function resolveCaption(string $captionField, EventParticipant $ep): ?string
    {
        return match (true) {
            $captionField === 'name' => $ep->participant->name,
            $captionField === 'invitation_code' => $ep->invitation?->invitation_code ?? '',
            $captionField === 'phone' => $ep->participant->phone_e164 ?? '',
            $captionField === 'none' => null,
            str_starts_with($captionField, 'meta.') => (string) data_get(
                $ep->participant->meta,
                substr($captionField, 5),
                ''
            ),
            default => $ep->participant->name,
        };
    }

    /**
     * Generate a QR PNG via GD (no imagick required).
     * Returns the path to the temp file; caller must delete it.
     */
    public function generateQrPng(string $content, int $pixelSize = 600, int $margin = 2): string
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
