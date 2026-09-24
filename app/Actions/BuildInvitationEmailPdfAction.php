<?php

namespace App\Actions;

use App\Models\EventParticipant;
use App\Models\PrintTemplate;
use App\Support\FpdfExtended;
use App\Support\InvitationCardRenderer;
use Illuminate\Support\Facades\Storage;

/**
 * Build a single participant's invitation PDF for email delivery: their card
 * (template or default design), followed by the event's static info PDF
 * pages (if one was uploaded), merged via FPDI.
 */
class BuildInvitationEmailPdfAction
{
    public function __construct(private readonly InvitationCardRenderer $renderer) {}

    public function execute(EventParticipant $ep): string
    {
        $event = $ep->event;
        $templateId = $event->settings['print_template_id'] ?? null;
        $template = $templateId ? PrintTemplate::find($templateId) : null;

        $pdf = $template
            ? new FpdfExtended('P', 'mm', [$template->page_width_mm, $template->page_height_mm])
            : new FpdfExtended('P', 'mm', [80, 105]);

        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        if ($template) {
            $captionField = $this->renderer->resolveCaptionField($event->settings['print_caption_field'] ?? 'name');
            $this->renderer->renderTemplateCard($pdf, $ep, $template, $captionField);
        } else {
            $this->renderer->renderElegantCard($pdf, $event, $ep);
        }

        if ($event->invitation_info_pdf_path) {
            $this->mergeStaticInfoPages($pdf, $event->invitation_info_pdf_path);
        }

        return $pdf->Output('S');
    }

    private function mergeStaticInfoPages(FpdfExtended $pdf, string $path): void
    {
        $pageCount = $pdf->setSourceFile(Storage::disk('public')->path($path));

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }
    }
}
