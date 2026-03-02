<?php

namespace App\Support;

use Codedge\Fpdf\Fpdf\Fpdf;

/**
 * Extends FPDF with a rounded-rectangle drawing primitive.
 *
 * FPDF exposes $k (scale factor) and $h (page height in user units) as
 * protected, so we must subclass to access them for raw PDF path commands.
 */
class FpdfExtended extends Fpdf
{
    /**
     * Draw a rounded rectangle using cubic Bézier curves.
     *
     * @param  string  $style  '' = outline only, 'F' = fill, 'FD'/'DF' = fill + outline
     */
    public function roundedRect(float $x, float $y, float $w, float $h, float $r, string $style = ''): void
    {
        $op = match (strtoupper($style)) {
            'F' => 'f',
            'FD', 'DF' => 'B',
            default => 'S',
        };

        // Bezier control-point factor for a quarter-circle approximation (κ ≈ 0.5523)
        $c = 0.5523 * $r;
        $hp = $this->h;   // page height in user units (mm)
        $k = $this->k;   // user-unit → PDF-point scale factor

        // Path: start after top-left corner radius, proceed clockwise
        $this->_out(sprintf('%.3F %.3F m',
            ($x + $r) * $k, ($hp - $y) * $k));

        // Top edge
        $this->_out(sprintf('%.3F %.3F l',
            ($x + $w - $r) * $k, ($hp - $y) * $k));
        // Top-right arc
        $this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c',
            ($x + $w - $r + $c) * $k, ($hp - $y) * $k,
            ($x + $w) * $k, ($hp - $y - $r + $c) * $k,
            ($x + $w) * $k, ($hp - $y - $r) * $k));

        // Right edge
        $this->_out(sprintf('%.3F %.3F l',
            ($x + $w) * $k, ($hp - $y - $h + $r) * $k));
        // Bottom-right arc
        $this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c',
            ($x + $w) * $k, ($hp - $y - $h + $r - $c) * $k,
            ($x + $w - $r + $c) * $k, ($hp - $y - $h) * $k,
            ($x + $w - $r) * $k, ($hp - $y - $h) * $k));

        // Bottom edge
        $this->_out(sprintf('%.3F %.3F l',
            ($x + $r) * $k, ($hp - $y - $h) * $k));
        // Bottom-left arc
        $this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c',
            ($x + $r - $c) * $k, ($hp - $y - $h) * $k,
            $x * $k, ($hp - $y - $h + $r - $c) * $k,
            $x * $k, ($hp - $y - $h + $r) * $k));

        // Left edge
        $this->_out(sprintf('%.3F %.3F l',
            $x * $k, ($hp - $y - $r) * $k));
        // Top-left arc
        $this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c',
            $x * $k, ($hp - $y - $r + $c) * $k,
            ($x + $r - $c) * $k, ($hp - $y) * $k,
            ($x + $r) * $k, ($hp - $y) * $k));

        $this->_out($op);
    }
}
