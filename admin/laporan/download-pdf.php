<?php
require_once '../includes/report-common.php';
requireReportAccess();
require_once '../../config/database.php';
require_once '../includes/simple-pdf.php';
require_once '../../includes/disease-education.php';

$reportType = (string) ($_GET['type'] ?? '');
$allowedTypes = ['penyakit', 'gejala', 'rule-base', 'hasil-diagnosis'];

if (!in_array($reportType, $allowedTypes, true)) {
    http_response_code(404);
    exit('Jenis laporan tidak ditemukan.');
}

if ($reportType !== 'hasil-diagnosis' && ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses laporan ditolak.');
}

$expertName = trim(strip_tags((string) ($_GET['pakar'] ?? '')));
if (function_exists('mb_substr')) {
    $expertName = mb_substr($expertName, 0, 100, 'UTF-8');
} else {
    $expertName = substr($expertName, 0, 100);
}
$expertName = $expertName !== '' ? $expertName : '................................';

$requestedScope = (string) ($_GET['scope'] ?? '');
$requestedHistoryId = (int) ($_GET['riwayat_id'] ?? 0);
$historyDiagnosisId = $reportType === 'hasil-diagnosis'
    && $requestedScope === 'history'
    ? resolveAccessibleDiagnosisId($requestedHistoryId)
    : 0;

if (
    $reportType === 'hasil-diagnosis'
    && $requestedScope === 'history'
    && $historyDiagnosisId <= 0
) {
    http_response_code(404);
    exit('Riwayat diagnosis tidak ditemukan atau tidak dapat diakses.');
}

$currentDiagnosisOnly = $reportType === 'hasil-diagnosis'
    && $historyDiagnosisId <= 0
    && (
        ($requestedScope === 'current')
        || (($_SESSION['user_role'] ?? '') !== 'admin')
    );
$report = buildReportData(
    $reportType,
    $currentDiagnosisOnly,
    $historyDiagnosisId
);
$pdf = new SimplePdfDocument();
$logoPath = dirname(__DIR__, 2) . '/assets/branding/aves-blue-report.jpg';
$margin = 30.0;
$tableWidth = array_sum(array_column($report['columns'], 'width'));
$pageNumber = 0;
$rowNumber = 0;

$drawDocumentHeader = function (bool $withTableHeader = true) use (
    $pdf,
    $report,
    $logoPath,
    $margin,
    $tableWidth,
    &$pageNumber
): float {
    $pdf->addPage();
    $pageNumber++;

    $navy = [0.059, 0.176, 0.353];
    $muted = [0.29, 0.33, 0.39];

    $pdf->imageJpeg($logoPath, $margin, 12, 78, 52);

    $headerTextX = $margin + 90;
    $pdf->text($headerTextX, 31, strtoupper($report['title']), 12.5, true, $navy);
    $pdf->wrappedText(
        $headerTextX,
        48,
        $tableWidth - 265,
        $report['description'],
        8.2,
        10,
        false,
        $muted
    );
    $pdf->text($margin + $tableWidth - 115, 31, 'Halaman ' . $pageNumber, 8, false, $muted);
    $pdf->text(
        $margin + $tableWidth - 175,
        48,
        'Dicetak: ' . date('d-m-Y H:i') . ' WIB',
        8,
        false,
        $muted
    );
    $pdf->line($margin, 82, $margin + $tableWidth, 82, 1.2, $navy);
    $pdf->line($margin, 85, $margin + $tableWidth, 85, 0.45, $navy);
    $pdf->text($margin, 101, 'Total data: ' . count($report['rows']), 8.5, true, $navy);

    if (!$withTableHeader) {
        return 118;
    }

    $headerTop = 112;
    $headerHeight = 25;
    $x = $margin;

    foreach ($report['columns'] as $column) {
        $pdf->rect($x, $headerTop, $column['width'], $headerHeight, $navy, $navy, 0.6);
        $lines = SimplePdfDocument::wrapText($column['label'], $column['width'] - 8, 7.7, true);
        $lineTop = $headerTop + 10 - ((count($lines) - 1) * 4);
        foreach ($lines as $lineIndex => $line) {
            $pdf->text(
                $x + 4,
                $lineTop + ($lineIndex * 9),
                $line,
                7.7,
                true,
                [1, 1, 1]
            );
        }
        $x += $column['width'];
    }

    return $headerTop + $headerHeight;
};

$fontSize = $report['font_size'];
$lineHeight = $report['line_height'];
$preparedRows = [];

foreach ($report['rows'] as $row) {
    $rowNumber++;
    $preparedLines = [];
    $maxLines = 1;

    foreach ($report['columns'] as $column) {
        $value = $column['key'] === 'no'
            ? (string) $rowNumber
            : (string) ($row[$column['key']] ?? '-');
        if (!empty($column['preserve_lines'])) {
            $lines = [];
            $forcedLines = preg_split('/\R/u', $value) ?: [$value];

            foreach ($forcedLines as $forcedLine) {
                $lines = array_merge(
                    $lines,
                    SimplePdfDocument::wrapText(
                        $forcedLine,
                        $column['width'] - 8,
                        $fontSize,
                        false
                    )
                );
            }
        } else {
            $lines = SimplePdfDocument::wrapText(
                $value,
                $column['width'] - 8,
                $fontSize,
                false
            );
        }
        $preparedLines[] = $lines;
        $maxLines = max($maxLines, count($lines));
    }

    $rowHeight = max(19, 8 + ($maxLines * $lineHeight));
    $preparedRows[] = [
        'number' => $rowNumber,
        'lines' => $preparedLines,
        'height' => $rowHeight,
    ];
}

/*
 * Sisakan ruang pada halaman tabel terakhir agar tanda tangan tidak
 * terdorong ke sebuah halaman kosong tersendiri.
 */
$signatureTableLimit = 410.0;
$finalRowsStart = count($preparedRows);
$finalRowsHeight = 0.0;

for ($index = count($preparedRows) - 1; $index >= 0; $index--) {
    $candidateHeight = $finalRowsHeight + $preparedRows[$index]['height'];
    if (137 + $candidateHeight > $signatureTableLimit) {
        break;
    }
    $finalRowsHeight = $candidateHeight;
    $finalRowsStart = $index;
}

$currentTop = $drawDocumentHeader(true);

foreach ($preparedRows as $preparedIndex => $preparedRow) {
    if (
        $preparedIndex === $finalRowsStart
        && $currentTop + $finalRowsHeight > $signatureTableLimit
    ) {
        $currentTop = $drawDocumentHeader(true);
    } elseif ($currentTop + $preparedRow['height'] > 548) {
        $currentTop = $drawDocumentHeader(true);
    }

    $fill = $preparedRow['number'] % 2 === 0 ? [0.973, 0.980, 0.988] : [1, 1, 1];
    $x = $margin;

    foreach ($report['columns'] as $columnIndex => $column) {
        $pdf->rect($x, $currentTop, $column['width'], $preparedRow['height'], $fill);
        foreach ($preparedRow['lines'][$columnIndex] as $lineIndex => $line) {
            $pdf->text(
                $x + 4,
                $currentTop + 12 + ($lineIndex * $lineHeight),
                $line,
                $fontSize,
                false
            );
        }
        $x += $column['width'];
    }

    $currentTop += $preparedRow['height'];
}

if (empty($report['rows'])) {
    $pdf->rect($margin, $currentTop, $tableWidth, 30, [0.973, 0.980, 0.988]);
    $pdf->text($margin + 8, $currentTop + 18, 'Belum ada data laporan.', 9, false, [0.29, 0.33, 0.39]);
    $currentTop += 30;
}

foreach (($report['recommendations'] ?? []) as $recommendationIndex => $recommendation) {
    $sectionWidth = $tableWidth;
    $contentWidth = $sectionWidth - 20;
    $recommendationTitle = 'Hasil diagnosis: ' . (string) $recommendation['diagnosis'];
    if (trim((string) ($recommendation['latin_name'] ?? '')) !== '') {
        $recommendationTitle .= ' (' . trim((string) $recommendation['latin_name']) . ')';
    }
    $solutionLines = SimplePdfDocument::wrapText(
        (string) $recommendation['solution'],
        $contentWidth,
        7.7,
        false
    );
    $therapyLines = SimplePdfDocument::wrapText(
        (string) $recommendation['therapy_note'],
        $contentWidth,
        7.7,
        false
    );
    $sourceLines = SimplePdfDocument::wrapText(
        'Sumber medis: ' . (string) $recommendation['source_title'],
        $contentWidth,
        7.2,
        false
    );
    $cardHeight = 115
        + (count($solutionLines) * 9)
        + (count($therapyLines) * 9)
        + (count($sourceLines) * 8);
    $sectionHeight = $cardHeight + 8 + ($recommendationIndex === 0 ? 25 : 0);

    if ($currentTop + $sectionHeight > 548) {
        $currentTop = $drawDocumentHeader(false);
    }

    if ($recommendationIndex === 0) {
        $pdf->text(
            $margin,
            $currentTop + 17,
            'REKOMENDASI PENANGANAN DAN TERAPI',
            10,
            true,
            [0.059, 0.176, 0.353]
        );
        $currentTop += 25;
    }

    $cardTop = $currentTop + 8;
    $pdf->rect(
        $margin,
        $cardTop,
        $sectionWidth,
        $cardHeight,
        [0.973, 0.984, 1],
        [0.70, 0.80, 0.91],
        0.6
    );
    $pdf->rect(
        $margin,
        $cardTop,
        $sectionWidth,
        23,
        [0.929, 0.961, 1],
        [0.70, 0.80, 0.91],
        0.6
    );
    $pdf->text(
        $margin + 10,
        $cardTop + 15,
        $recommendationTitle,
        8.4,
        true,
        [0.059, 0.176, 0.353]
    );

    $textTop = $cardTop + 39;
    $pdf->text($margin + 10, $textTop, 'Penanganan awal', 8, true, [0.059, 0.176, 0.353]);
    $textTop += 12;
    foreach ($solutionLines as $line) {
        $pdf->text($margin + 10, $textTop, $line, 7.7);
        $textTop += 9;
    }

    $textTop += 5;
    $pdf->text(
        $margin + 10,
        $textTop,
        strtoupper((string) $recommendation['therapy_kind']),
        7.2,
        true,
        [0.016, 0.471, 0.341]
    );
    $textTop += 12;
    $pdf->text(
        $margin + 10,
        $textTop,
        (string) $recommendation['therapy_name'],
        9,
        true,
        [0.059, 0.176, 0.353]
    );
    $textTop += 12;
    foreach ($therapyLines as $line) {
        $pdf->text($margin + 10, $textTop, $line, 7.7);
        $textTop += 9;
    }

    $textTop += 5;
    $pdf->text(
        $margin + 10,
        $textTop,
        'PERINGATAN: Jangan memberikan obat sendiri. Informasi terapi ini bukan resep;',
        7.3,
        true,
        [0.45, 0.27, 0]
    );
    $textTop += 9;
    $pdf->text(
        $margin + 10,
        $textTop,
        'dosis, rute, dan lama pemberian harus ditentukan dokter hewan.',
        7.3,
        false,
        [0.45, 0.27, 0]
    );
    $textTop += 13;
    foreach ($sourceLines as $line) {
        $pdf->text($margin + 10, $textTop, $line, 7.2, false, [0.29, 0.33, 0.39]);
        $textTop += 8;
    }

    $currentTop = $cardTop + $cardHeight;
}

if ($currentTop + 125 > 558) {
    $currentTop = $drawDocumentHeader(false);
}

$signatureWidth = 220;
$signatureX = $margin + $tableWidth - $signatureWidth;
$signatureTop = max($currentTop + 24, 150);
$pdf->text($signatureX + 20, $signatureTop, 'Jakarta, ' . formatIndonesianFullDate(), 9);
$pdf->text($signatureX + 95, $signatureTop + 17, 'Administrator', 9, true);
$pdf->line(
    $signatureX + 10,
    $signatureTop + 86,
    $signatureX + $signatureWidth - 10,
    $signatureTop + 86,
    0.7,
    [0.07, 0.10, 0.16]
);
$nameLines = SimplePdfDocument::wrapText($expertName, $signatureWidth - 24, 9.2, true);
$pdf->text(
    $signatureX + 16,
    $signatureTop + 82,
    implode(' ', $nameLines),
    9.2,
    true
);

$filename = $report['filename'] . '-' . date('Ymd') . '.pdf';
$pdf->output($filename);

function buildReportData(
    string $reportType,
    bool $currentDiagnosisOnly = false,
    int $historyDiagnosisId = 0
): array
{
    if ($reportType === 'penyakit') {
        return [
            'title' => 'Laporan Data Penyakit',
            'description' => 'Daftar seluruh penyakit kulit burung yang menjadi dasar proses diagnosis.',
            'filename' => 'laporan-data-penyakit',
            'font_size' => 7.2,
            'line_height' => 8.8,
            'columns' => [
                ['key' => 'no', 'label' => 'No', 'width' => 28],
                ['key' => 'kode_penyakit', 'label' => 'Kode Penyakit', 'width' => 65],
                ['key' => 'nama_penyakit', 'label' => 'Nama Penyakit', 'width' => 135],
                ['key' => 'deskripsi', 'label' => 'Deskripsi', 'width' => 270],
                ['key' => 'solusi', 'label' => 'Saran Penanganan', 'width' => 283],
            ],
            'rows' => fetchAll(
                'SELECT kode_penyakit, nama_penyakit, deskripsi, solusi
                 FROM penyakit ORDER BY kode_penyakit'
            ),
        ];
    }

    if ($reportType === 'gejala') {
        return [
            'title' => 'Laporan Data Gejala',
            'description' => 'Daftar gejala penyakit kulit burung yang tersedia dalam proses konsultasi.',
            'filename' => 'laporan-data-gejala',
            'font_size' => 8.2,
            'line_height' => 10,
            'columns' => [
                ['key' => 'no', 'label' => 'No', 'width' => 35],
                ['key' => 'kode_gejala', 'label' => 'Kode Gejala', 'width' => 95],
                ['key' => 'nama_gejala', 'label' => 'Nama Gejala', 'width' => 651],
            ],
            'rows' => fetchAll(
                'SELECT kode_gejala, nama_gejala FROM gejala ORDER BY kode_gejala'
            ),
        ];
    }

    if ($reportType === 'rule-base') {
        return [
            'title' => 'Laporan Basis Pengetahuan / Rule Base',
            'description' => 'Hubungan penyakit, gejala, dan nilai Certainty Factor yang diberikan pakar.',
            'filename' => 'laporan-basis-pengetahuan',
            'font_size' => 6.8,
            'line_height' => 8.2,
            'columns' => [
                ['key' => 'no', 'label' => 'No', 'width' => 28],
                ['key' => 'kode_penyakit', 'label' => 'Kode Penyakit', 'width' => 62],
                ['key' => 'nama_penyakit', 'label' => 'Nama Penyakit', 'width' => 145],
                ['key' => 'kode_gejala', 'label' => 'Kode Gejala', 'width' => 62],
                ['key' => 'nama_gejala', 'label' => 'Nama Gejala', 'width' => 424],
                ['key' => 'cf_pakar_formatted', 'label' => 'CF Pakar', 'width' => 60],
            ],
            'rows' => array_map(
                static function (array $row): array {
                    $row['cf_pakar_formatted'] = number_format((float) $row['cf_pakar'], 1, ',', '');
                    return $row;
                },
                fetchAll(
                    'SELECT p.kode_penyakit, p.nama_penyakit,
                            g.kode_gejala, g.nama_gejala, a.cf_pakar
                     FROM aturan a
                     JOIN penyakit p ON p.id_penyakit = a.id_penyakit
                     JOIN gejala g ON g.id_gejala = a.id_gejala
                     ORDER BY p.kode_penyakit, g.kode_gejala'
                )
            ),
        ];
    }

    $symptomMap = [];
    foreach (fetchAll('SELECT id_gejala, kode_gejala, nama_gejala FROM gejala') as $symptom) {
        $symptomMap[(string) $symptom['id_gejala']] = $symptom;
    }

    $historySql = 'SELECT r.tanggal, r.detail_gejala, r.penyakit_tertinggi,
                          r.nilai_cf, u.nama, p.kode_penyakit, p.nama_latin, p.solusi
                   FROM riwayat r
                   JOIN users u ON u.id = r.id_user
                   LEFT JOIN penyakit p ON p.nama_penyakit = r.penyakit_tertinggi';
    $historyParams = [];

    if ($historyDiagnosisId > 0) {
        $historySql .= ' WHERE r.id_riwayat = ?';
        $historyParams[] = $historyDiagnosisId;
    } elseif ($currentDiagnosisOnly) {
        $historySql .= ' WHERE r.id_riwayat = ? AND r.id_user = ?';
        $historyParams[] = resolveCurrentDiagnosisId();
        $historyParams[] = (int) $_SESSION['user_id'];
    }

    $historySql .= ' ORDER BY r.tanggal DESC, r.id_riwayat DESC';
    $historyRows = fetchAll($historySql, $historyParams);
    $preparedRows = [];
    $recommendations = [];

    foreach ($historyRows as $row) {
        $selectedSymptoms = json_decode($row['detail_gejala'] ?? '[]', true);
        $symptomLabels = [];

        if (is_array($selectedSymptoms)) {
            foreach ($selectedSymptoms as $symptomId => $cfUser) {
                if (isset($symptomMap[(string) $symptomId])) {
                    $symptom = $symptomMap[(string) $symptomId];
                    $symptomLabels[] = $symptom['kode_gejala'] . ' - ' . $symptom['nama_gejala'];
                } else {
                    $symptomLabels[] = 'Gejala basis pengetahuan lama';
                }
            }
        }

        $numberedSymptomLabels = [];
        foreach ($symptomLabels as $symptomIndex => $symptomLabel) {
            $numberedSymptomLabels[] = ($symptomIndex + 1) . '. ' . $symptomLabel;
        }

        $diagnosisLabel = (string) ($row['penyakit_tertinggi'] ?? '-');
        $latinName = trim((string) ($row['nama_latin'] ?? ''));
        if ($latinName !== '') {
            $diagnosisLabel .= "\n" . $latinName;
        }

        $preparedRows[] = [
            'tanggal_formatted' => date('d-m-Y H:i', strtotime($row['tanggal'])),
            'nama' => $row['nama'],
            'gejala' => implode("\n", $numberedSymptomLabels) ?: '-',
            'penyakit_tertinggi' => $diagnosisLabel,
            'nilai_cf_formatted' => number_format((float) $row['nilai_cf'], 3, ',', ''),
            'persentase' => number_format((float) $row['nilai_cf'] * 100, 1, ',', '') . '%',
        ];

        if ($historyDiagnosisId > 0 || $currentDiagnosisOnly) {
            $education = getDiseaseEducation((string) ($row['kode_penyakit'] ?? ''));
            $recommendations[] = [
                'diagnosis' => (string) ($row['penyakit_tertinggi'] ?? '-'),
                'latin_name' => $latinName,
                'solution' => trim((string) ($row['solusi'] ?? ''))
                    ?: 'Segera konsultasikan kondisi burung kepada dokter hewan.',
                'therapy_name' => (string) (
                    $education['therapy_name']
                    ?? 'Konsultasikan dengan dokter hewan'
                ),
                'therapy_kind' => (string) ($education['therapy_kind'] ?? 'Terapi dokter hewan'),
                'therapy_note' => (string) (
                    $education['therapy_note']
                    ?? 'Jenis terapi perlu ditentukan setelah pemeriksaan dokter hewan.'
                ),
                'source_title' => (string) ($education['source']['title'] ?? 'Basis pengetahuan AVES'),
            ];
        }
    }

    return [
        'title' => $historyDiagnosisId > 0
            ? 'Laporan Detail Hasil Diagnosis'
            : (
                $currentDiagnosisOnly
                    ? 'Laporan Hasil Diagnosis Terkini'
                    : 'Laporan Hasil Diagnosis'
            ),
        'description' => $historyDiagnosisId > 0
            ? 'Satu hasil diagnosis yang dipilih dari riwayat konsultasi pengguna.'
            : (
                $currentDiagnosisOnly
                    ? 'Satu hasil diagnosis terbaru yang baru saja dihasilkan dari proses konsultasi.'
                    : 'Seluruh riwayat konsultasi pengguna dan hasil akhir perhitungan Certainty Factor.'
            ),
        'filename' => $historyDiagnosisId > 0
            ? 'laporan-detail-hasil-diagnosis-' . $historyDiagnosisId
            : (
                $currentDiagnosisOnly
                    ? 'laporan-hasil-diagnosis-terkini'
                    : 'laporan-hasil-diagnosis'
            ),
        'font_size' => 6.2,
        'line_height' => 7.2,
        'columns' => [
            ['key' => 'no', 'label' => 'No', 'width' => 27],
            ['key' => 'tanggal_formatted', 'label' => 'Tanggal', 'width' => 65],
            ['key' => 'nama', 'label' => 'Nama Pengguna', 'width' => 80],
            ['key' => 'gejala', 'label' => 'Gejala Dipilih', 'width' => 360, 'preserve_lines' => true],
            ['key' => 'penyakit_tertinggi', 'label' => 'Hasil Diagnosis', 'width' => 130, 'preserve_lines' => true],
            ['key' => 'nilai_cf_formatted', 'label' => 'Nilai CF', 'width' => 54],
            ['key' => 'persentase', 'label' => 'Persentase', 'width' => 65],
        ],
        'rows' => $preparedRows,
        'recommendations' => $recommendations,
    ];
}
