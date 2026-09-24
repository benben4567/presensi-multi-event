<?php

namespace App\Support;

use Codedge\Fpdf\Fpdf\Fpdf;
use setasign\Fpdi\FpdfTplTrait;

/**
 * Mirrors FPDI's own `FpdfTpl` class (which extends the *global* `\FPDF`)
 * but built on Codedge's namespaced `Fpdf` fork instead — this project
 * doesn't use the global FPDF class.
 *
 * Needed as its own class (not merged into FpdfExtended) so that
 * `FpdfTrait::getTemplateSize()`'s `parent::getTemplateSize()` call
 * resolves to this class's (trait-provided) implementation, exactly as it
 * does for FPDI's own `Fpdi extends FpdfTpl` composition — `parent::` inside
 * a trait follows the real class hierarchy, not other traits used
 * side-by-side.
 */
class FpdiCompatibleFpdf extends Fpdf
{
    use FpdfTplTrait;
}
