<?php

/**
 * Generator PDF ringan tanpa dependensi eksternal.
 *
 * Kelas ini menyediakan kebutuhan dasar laporan AVES Jakarta: teks,
 * pembungkusan teks, garis, kotak, warna, dan beberapa halaman A4 landscape.
 */
final class SimplePdfDocument
{
    private float $width = 841.89;
    private float $height = 595.28;
    private array $pages = [];
    private array $pageImages = [];
    private array $images = [];
    private array $imageNamesByPath = [];
    private int $activePage = -1;

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->activePage = count($this->pages) - 1;
        $this->pageImages[$this->activePage] = [];
    }

    public function getPageCount(): int
    {
        return count($this->pages);
    }

    public function text(
        float $x,
        float $top,
        string $text,
        float $fontSize = 10,
        bool $bold = false,
        array $color = [0.07, 0.10, 0.16]
    ): void {
        $font = $bold ? 'F2' : 'F1';
        $encodedText = $this->escapeText($text);
        $y = $this->height - $top;

        $this->append(sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET\n",
            $font,
            $fontSize,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $encodedText
        ));
    }

    public function line(
        float $x1,
        float $top1,
        float $x2,
        float $top2,
        float $width = 0.6,
        array $color = [0.61, 0.64, 0.69]
    ): void {
        $this->append(sprintf(
            "%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            $color[0],
            $color[1],
            $color[2],
            $width,
            $x1,
            $this->height - $top1,
            $x2,
            $this->height - $top2
        ));
    }

    public function rect(
        float $x,
        float $top,
        float $width,
        float $height,
        ?array $fill = null,
        array $stroke = [0.61, 0.64, 0.69],
        float $strokeWidth = 0.6
    ): void {
        $bottom = $this->height - $top - $height;

        if ($fill !== null) {
            $this->append(sprintf(
                "%.3F %.3F %.3F rg %.3F %.3F %.3F RG %.2F w %.2F %.2F %.2F %.2F re B\n",
                $fill[0],
                $fill[1],
                $fill[2],
                $stroke[0],
                $stroke[1],
                $stroke[2],
                $strokeWidth,
                $x,
                $bottom,
                $width,
                $height
            ));
            return;
        }

        $this->append(sprintf(
            "%.3F %.3F %.3F RG %.2F w %.2F %.2F %.2F %.2F re S\n",
            $stroke[0],
            $stroke[1],
            $stroke[2],
            $strokeWidth,
            $x,
            $bottom,
            $width,
            $height
        ));
    }

    public function circle(
        float $centerX,
        float $centerTop,
        float $radius,
        ?array $fill = null,
        array $stroke = [0.61, 0.64, 0.69],
        float $strokeWidth = 0.6
    ): void {
        $centerY = $this->height - $centerTop;
        $control = $radius * 0.5522847498;
        $paintOperator = $fill !== null ? 'B' : 'S';
        $fillCommand = $fill !== null
            ? sprintf("%.3F %.3F %.3F rg ", $fill[0], $fill[1], $fill[2])
            : '';

        $this->append(sprintf(
            "%s%.3F %.3F %.3F RG %.2F w "
            . "%.2F %.2F m "
            . "%.2F %.2F %.2F %.2F %.2F %.2F c "
            . "%.2F %.2F %.2F %.2F %.2F %.2F c "
            . "%.2F %.2F %.2F %.2F %.2F %.2F c "
            . "%.2F %.2F %.2F %.2F %.2F %.2F c %s\n",
            $fillCommand,
            $stroke[0],
            $stroke[1],
            $stroke[2],
            $strokeWidth,
            $centerX + $radius,
            $centerY,
            $centerX + $radius,
            $centerY + $control,
            $centerX + $control,
            $centerY + $radius,
            $centerX,
            $centerY + $radius,
            $centerX - $control,
            $centerY + $radius,
            $centerX - $radius,
            $centerY + $control,
            $centerX - $radius,
            $centerY,
            $centerX - $radius,
            $centerY - $control,
            $centerX - $control,
            $centerY - $radius,
            $centerX,
            $centerY - $radius,
            $centerX + $control,
            $centerY - $radius,
            $centerX + $radius,
            $centerY - $control,
            $centerX + $radius,
            $centerY,
            $paintOperator
        ));
    }

    public function imageJpeg(
        string $path,
        float $x,
        float $top,
        float $width,
        float $height
    ): void {
        if ($this->activePage < 0) {
            $this->addPage();
        }

        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            throw new RuntimeException('Gambar JPEG untuk PDF tidak ditemukan.');
        }

        $imageInfo = @getimagesize($realPath);
        if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== 'image/jpeg') {
            throw new RuntimeException('SimplePdfDocument hanya mendukung gambar JPEG.');
        }

        if (!isset($this->imageNamesByPath[$realPath])) {
            $imageData = file_get_contents($realPath);
            if ($imageData === false) {
                throw new RuntimeException('Gambar JPEG untuk PDF tidak dapat dibaca.');
            }

            $imageName = 'Im' . (count($this->images) + 1);
            $this->images[$imageName] = [
                'data' => $imageData,
                'width' => (int) $imageInfo[0],
                'height' => (int) $imageInfo[1],
            ];
            $this->imageNamesByPath[$realPath] = $imageName;
        }

        $imageName = $this->imageNamesByPath[$realPath];
        $this->pageImages[$this->activePage][$imageName] = true;
        $bottom = $this->height - $top - $height;

        $this->append(sprintf(
            "q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n",
            $width,
            $height,
            $x,
            $bottom,
            $imageName
        ));
    }

    public function wrappedText(
        float $x,
        float $top,
        float $width,
        string $text,
        float $fontSize = 9,
        float $lineHeight = 11,
        bool $bold = false,
        array $color = [0.07, 0.10, 0.16]
    ): int {
        $lines = self::wrapText($text, $width, $fontSize, $bold);

        foreach ($lines as $index => $line) {
            $this->text(
                $x,
                $top + ($index * $lineHeight),
                $line,
                $fontSize,
                $bold,
                $color
            );
        }

        return count($lines);
    }

    public static function wrapText(
        string $text,
        float $width,
        float $fontSize,
        bool $bold = false
    ): array {
        $text = trim((string) preg_replace('/\s+/u', ' ', str_replace(["\r", "\n"], ' ', $text)));
        if ($text === '') {
            return ['-'];
        }

        $averageCharacterWidth = $fontSize * ($bold ? 0.56 : 0.51);
        $maxCharacters = max(1, (int) floor($width / $averageCharacterWidth));
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            while (self::textLength($word) > $maxCharacters) {
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                    $currentLine = '';
                }

                $lines[] = self::textSlice($word, 0, $maxCharacters);
                $word = self::textSlice($word, $maxCharacters);
            }

            $candidate = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            if (self::textLength($candidate) <= $maxCharacters) {
                $currentLine = $candidate;
                continue;
            }

            if ($currentLine !== '') {
                $lines[] = $currentLine;
            }
            $currentLine = $word;
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines ?: ['-'];
    }

    public function output(string $filename): void
    {
        if (empty($this->pages)) {
            $this->addPage();
        }

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $nextObjectId = 5;
        $imageObjectIds = [];
        foreach ($this->images as $imageName => $image) {
            $imageObjectIds[$imageName] = $nextObjectId;
            $objects[$nextObjectId] = sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d "
                . "/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode "
                . "/Length %d >>\nstream\n%s\nendstream",
                $image['width'],
                $image['height'],
                strlen($image['data']),
                $image['data']
            );
            $nextObjectId++;
        }

        $pageIds = [];
        foreach ($this->pages as $index => $content) {
            $contentId = $nextObjectId++;
            $pageId = $nextObjectId++;
            $pageIds[] = $pageId . ' 0 R';
            $objects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n"
                . $content . "endstream";

            $xObjectEntries = [];
            foreach (array_keys($this->pageImages[$index] ?? []) as $imageName) {
                $xObjectEntries[] = '/' . $imageName . ' '
                    . $imageObjectIds[$imageName] . ' 0 R';
            }
            $xObjectResources = $xObjectEntries === []
                ? ''
                : ' /XObject << ' . implode(' ', $xObjectEntries) . ' >>';

            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >>%s >> '
                . '/Contents %d 0 R >>',
                $this->width,
                $this->height,
                $xObjectResources,
                $contentId
            );
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageIds)
            . '] /Count ' . count($pageIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        $maxObjectId = max(array_keys($objects));

        for ($objectId = 1; $objectId <= $maxObjectId; $objectId++) {
            $offsets[$objectId] = strlen($pdf);
            $pdf .= $objectId . " 0 obj\n" . $objects[$objectId] . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxObjectId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($objectId = 1; $objectId <= $maxObjectId; $objectId++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectId]);
        }

        $pdf .= "trailer\n<< /Size " . ($maxObjectId + 1)
            . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?: 'laporan.pdf';
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('X-Content-Type-Options: nosniff');
        echo $pdf;
        exit;
    }

    private function append(string $command): void
    {
        if ($this->activePage < 0) {
            $this->addPage();
        }

        $this->pages[$this->activePage] .= $command;
    }

    private function escapeText(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($encoded === false) {
            $encoded = $text;
        }

        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\\(', '\\)', ' ', ' '],
            $encoded
        );
    }

    private static function textLength(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    private static function textSlice(string $text, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return $length === null
                ? mb_substr($text, $start, null, 'UTF-8')
                : mb_substr($text, $start, $length, 'UTF-8');
        }

        return $length === null ? substr($text, $start) : substr($text, $start, $length);
    }
}
