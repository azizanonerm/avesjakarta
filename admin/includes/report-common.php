<?php

require_once dirname(__DIR__, 2) . '/config/security.php';

function formatIndonesianFullDate(?DateTimeInterface $date = null): string
{
    $date ??= new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));

    $dayNames = [
        'Minggu',
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
    ];
    $monthNames = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    return $dayNames[(int) $date->format('w')]
        . ', '
        . $date->format('j')
        . ' '
        . $monthNames[(int) $date->format('n')]
        . ' '
        . $date->format('Y');
}

function requireReportAccess(): void
{
    requireAuthenticatedUser('../../login.php');
}

function requireAdminReportAccess(): void
{
    requireAdminUser('../../login.php');
}

function renderReportStart(
    string $title,
    string $description,
    int $totalData,
    string $downloadType,
    array $downloadParams = []
): void
{
    $printedAt = date('d-m-Y H:i');
    $downloadQuery = http_build_query(
        array_merge(['type' => $downloadType], $downloadParams)
    );
    $downloadHref = 'download-pdf.php?' . $downloadQuery;
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../../includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title><?php echo htmlspecialchars($title); ?> - AVES</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0f2d5a;
            --blue: #075fbd;
            --line: #9ca3af;
            --soft: #eff6ff;
            --text: #111827;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #eef2f7;
            color: var(--text);
            font-family: Inter, Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            background: var(--navy);
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,.18);
        }

        .toolbar a,
        .toolbar button {
            display: inline-flex;
            flex: 0 0 auto;
            width: fit-content;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.45);
            border-radius: 10px;
            background: white;
            color: var(--navy);
            padding: 9px 12px;
            font: inherit;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar .download-pdf {
            border-color: #0867c5;
            background: #0867c5;
            color: white;
        }

        .report-page {
            display: flex;
            flex-direction: column;
            width: min(1200px, calc(100% - 32px));
            min-height: 190mm;
            margin: 24px auto;
            padding: 18mm 14mm 14mm;
            background: white;
            box-shadow: 0 3px 18px rgba(15,45,90,.16);
        }

        .report-header {
            padding-bottom: 12px;
            margin-bottom: 18px;
            border-bottom: 3px double var(--navy);
            text-align: center;
        }

        .report-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .report-brand img {
            display: block;
            width: 172px;
            height: auto;
            flex: 0 0 auto;
            object-fit: contain;
        }

        .report-header h1 {
            margin: 0;
            color: var(--navy);
            font-family: "Plus Jakarta Sans", Inter, Arial, sans-serif;
            font-size: 25px;
            letter-spacing: .3px;
        }

        .report-header h2 {
            margin: 7px 0 4px;
            font-family: "Plus Jakarta Sans", Inter, Arial, sans-serif;
            font-size: 18px;
            text-transform: uppercase;
        }

        .report-header p {
            margin: 3px 0;
            color: #4b5563;
            line-height: 1.5;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
            font-size: 11px;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        th, td {
            border: 1px solid var(--line);
            padding: 7px 8px;
            vertical-align: top;
            line-height: 1.4;
        }

        th {
            background: var(--navy);
            color: white;
            text-align: center;
            font-weight: 700;
        }

        tbody tr:nth-child(even) { background: #f8fafc; }
        .number { width: 44px; text-align: center; }
        .code { white-space: nowrap; font-weight: 700; }
        .center { text-align: center; }
        .empty { padding: 24px; text-align: center; color: #6b7280; }

        .signature-area {
            width: 250px;
            margin-top: auto;
            margin-left: auto;
            padding-top: 28px;
            break-inside: avoid;
            page-break-inside: avoid;
            text-align: center;
        }

        .signature-area p { margin: 2px 0; }
        .signature-space { height: 70px; }

        .expert-name-print {
            display: inline-block;
            min-width: 210px;
            padding: 0 8px 4px;
            border-bottom: 1px solid #111827;
            font-weight: 700;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            body { background: white; font-size: 9.5px; }
            .no-print, .aves-global-back { display: none !important; }
            .report-page {
                width: 100%;
                min-height: 185mm;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .report-header { margin-bottom: 10px; }
            .report-brand { gap: 9px; }
            .report-brand img {
                width: 128px;
                height: auto;
                flex-basis: auto;
            }
            .report-header h1 { font-size: 20px; }
            .report-header h2 { font-size: 15px; }
            th, td { padding: 4px 5px; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; page-break-inside: avoid; }
            .signature-area { padding-top: 15px; }
        }

        @media (max-width: 720px) {
            .toolbar { flex-wrap: wrap; }
            .report-page { width: calc(100% - 16px); margin: 8px; padding: 18px 12px; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print" data-aves-back-host data-aves-back-position="start">
        <button type="button" onclick="window.print()">Cetak</button>
        <a
            class="download-pdf"
            href="<?php echo htmlspecialchars($downloadHref, ENT_QUOTES, 'UTF-8'); ?>"
        >Unduh PDF</a>
    </div>

    <main class="report-page">
        <header class="report-header">
            <div class="report-brand">
                <img src="../../assets/branding/aves-blue.png" alt="AVES - Avian &amp; Exotic Animal Veterinary Services Jakarta">
            </div>
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <p><?php echo htmlspecialchars($description); ?></p>
        </header>
        <div class="report-meta">
            <span>Total data: <strong><?php echo $totalData; ?></strong></span>
            <span>Dicetak: <?php echo htmlspecialchars($printedAt); ?> WIB</span>
        </div>
    <?php
}

function resolveCurrentDiagnosisId(): int
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return 0;
    }

    $sessionDiagnosisId = (int) ($_SESSION['latest_diagnosis_id'] ?? 0);
    if ($sessionDiagnosisId > 0) {
        $ownedDiagnosis = fetchOne(
            'SELECT id_riwayat
             FROM riwayat
             WHERE id_riwayat = ? AND id_user = ?',
            [$sessionDiagnosisId, $userId]
        );

        if ($ownedDiagnosis) {
            return (int) $ownedDiagnosis['id_riwayat'];
        }
    }

    $latestDiagnosis = fetchOne(
        'SELECT id_riwayat
         FROM riwayat
         WHERE id_user = ?
         ORDER BY tanggal DESC, id_riwayat DESC
         LIMIT 1',
        [$userId]
    );

    $latestDiagnosisId = (int) ($latestDiagnosis['id_riwayat'] ?? 0);
    if ($latestDiagnosisId > 0) {
        $_SESSION['latest_diagnosis_id'] = $latestDiagnosisId;
    }

    return $latestDiagnosisId;
}

function resolveAccessibleDiagnosisId(int $diagnosisId): int
{
    if ($diagnosisId <= 0) {
        return 0;
    }

    $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
    $params = [$diagnosisId];
    $query = 'SELECT id_riwayat FROM riwayat WHERE id_riwayat = ?';

    if (!$isAdmin) {
        $query .= ' AND id_user = ?';
        $params[] = (int) ($_SESSION['user_id'] ?? 0);
    }

    $diagnosis = fetchOne($query, $params);

    return (int) ($diagnosis['id_riwayat'] ?? 0);
}

function renderReportEnd(): void
{
    $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
    ?>
        <section class="signature-area">
            <p>Jakarta, <span id="signature-date"><?php echo htmlspecialchars(formatIndonesianFullDate()); ?></span></p>
            <p><strong>Administrator</strong></p>
            <div class="signature-space" aria-label="Area tanda tangan administrator"></div>
            <p class="expert-name-print" id="expert-name-print">................................</p>
        </section>
    </main>

    <?php
    $scope = (string) ($_GET['scope'] ?? '');
    $historyId = (int) ($_GET['riwayat_id'] ?? 0);
    $isHistoryReport = $scope === 'history' && $historyId > 0;
    $returnPage = max(1, filter_input(INPUT_GET, 'return_page', FILTER_VALIDATE_INT) ?: 1);
    $returnSearch = limitedText($_GET['return_search'] ?? '', 100);

    if ($isAdmin && $isHistoryReport) {
        $detailQuery = ['id' => $historyId, 'return_page' => $returnPage];
        if ($returnSearch !== '') {
            $detailQuery['return_search'] = $returnSearch;
        }
        $back_url = '../detail-riwayat.php?' . http_build_query($detailQuery);
        $back_label = 'Detail Riwayat Diagnosis';
        $back_use_history = false;
    } elseif ($isAdmin) {
        $back_url = '../laporan.php';
        $back_label = 'Menu Laporan';
        $back_use_history = false;
    } elseif ($isHistoryReport) {
        $back_url = '../../detail-riwayat.php?id=' . $historyId;
        $back_label = 'Detail Riwayat';
        $back_use_history = false;
    } else {
        $back_url = '../../hasil_diagnosa.php';
        $back_label = 'Hasil Diagnosis';
        $back_use_history = false;
    }
    include dirname(__DIR__, 2) . '/includes/back-button.php';
    ?>
</body>
</html>
    <?php
}
