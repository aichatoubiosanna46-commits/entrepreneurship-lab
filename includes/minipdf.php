<?php
// ============================================================
//  includes/minipdf.php — Générateur PDF minimal sans dépendance
//  (pas de Composer en prod sur InfinityFree). Suffisant pour une
//  facture simple : texte positionné + lignes, une seule page A4.
// ============================================================

class MiniPDF
{
    /** @var array<int, string> Contenu du flux de la page (instructions PDF) */
    private array $ops = [];
    private float $pageW = 595.28; // A4 portrait en points (72dpi)
    private float $pageH = 841.89;
    private string $font = 'Helvetica';

    public function text(float $x, float $y, string $txt, float $size = 11, bool $bold = false): void
    {
        $f = $bold ? 'F2' : 'F1';
        $escaped = $this->escape($txt);
        $yPdf = $this->pageH - $y;
        $this->ops[] = "BT /$f $size Tf $x $yPdf Td ($escaped) Tj ET";
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.5): void
    {
        $y1p = $this->pageH - $y1;
        $y2p = $this->pageH - $y2;
        $this->ops[] = "$width w $x1 $y1p m $x2 $y2p l S";
    }

    public function rect(float $x, float $y, float $w, float $h, ?string $fillHex = null): void
    {
        $yp = $this->pageH - $y - $h;
        if ($fillHex) {
            [$r, $g, $b] = $this->hexToRgb($fillHex);
            $this->ops[] = "$r $g $b rg $x $yp $w $h re f";
        } else {
            $this->ops[] = "$x $yp $w $h re S";
        }
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        return [round($r, 3), round($g, 3), round($b, 3)];
    }

    private function escape(string $txt): string
    {
        // Convertit en latin1 (WinAnsiEncoding) pour les accents français, sinon échappe les parenthèses/backslash
        $txt = @iconv('UTF-8', 'CP1252//IGNORE', $txt) ?: $txt;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $txt);
    }

    public function output(): string
    {
        $content = implode("\n", $this->ops);
        $objects = [];

        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /MediaBox [0 0 {$this->pageW} {$this->pageH}] /Contents 4 0 R >>";
        $objects[4] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$body\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";
        return $pdf;
    }

    public function streamDownload(string $filename): void
    {
        $pdf = $this->output();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }
}
