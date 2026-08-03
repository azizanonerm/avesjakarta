<?php
require_once '../includes/report-common.php';
requireReportAccess();
require_once '../../config/database.php';
require_once '../../includes/disease-education.php';

$symptomMap = [];
foreach (fetchAll('SELECT id_gejala, kode_gejala, nama_gejala FROM gejala') as $symptom) {
    $symptomMap[(string) $symptom['id_gejala']] = $symptom;
}

$historySql = 'SELECT r.id_riwayat, r.tanggal, r.detail_gejala,
                      r.penyakit_tertinggi, r.nilai_cf, u.nama,
                      p.kode_penyakit, p.nama_latin, p.solusi
               FROM riwayat r
               JOIN users u ON u.id = r.id_user
               LEFT JOIN penyakit p ON p.nama_penyakit = r.penyakit_tertinggi';
$historyParams = [];
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$requestedScope = (string) ($_GET['scope'] ?? '');
$requestedHistoryId = (int) ($_GET['riwayat_id'] ?? 0);
$historyOnly = $requestedScope === 'history' && $requestedHistoryId > 0;
$historyDiagnosisId = $historyOnly
    ? resolveAccessibleDiagnosisId($requestedHistoryId)
    : 0;
$currentOnly = !$historyOnly
    && (!$isAdmin || $requestedScope === 'current');

if ($historyOnly) {
    $historySql .= ' WHERE r.id_riwayat = ?';
    $historyParams[] = $historyDiagnosisId;
} elseif ($currentOnly) {
    $historySql .= ' WHERE r.id_riwayat = ? AND r.id_user = ?';
    $historyParams[] = resolveCurrentDiagnosisId();
    $historyParams[] = (int) $_SESSION['user_id'];
}

$historySql .= ' ORDER BY r.tanggal DESC, r.id_riwayat DESC';
$rows = fetchAll($historySql, $historyParams);
$recommendations = [];

foreach (($historyOnly || $currentOnly) ? $rows : [] as $row) {
    $education = getDiseaseEducation((string) ($row['kode_penyakit'] ?? ''));
    $recommendations[] = [
        'diagnosis' => (string) ($row['penyakit_tertinggi'] ?? '-'),
        'latin_name' => trim((string) ($row['nama_latin'] ?? '')),
        'solution' => trim((string) ($row['solusi'] ?? '')),
        'therapy_name' => (string) ($education['therapy_name'] ?? 'Konsultasikan dengan dokter hewan'),
        'therapy_kind' => (string) ($education['therapy_kind'] ?? 'Terapi dokter hewan'),
        'therapy_note' => (string) (
            $education['therapy_note']
            ?? 'Jenis terapi perlu ditentukan setelah pemeriksaan dokter hewan.'
        ),
        'source_title' => (string) ($education['source']['title'] ?? ''),
        'source_url' => (string) ($education['source']['url'] ?? ''),
    ];
}

renderReportStart(
    $historyOnly
        ? 'Laporan Detail Hasil Diagnosis'
        : ($currentOnly ? 'Laporan Hasil Diagnosis Terkini' : 'Laporan Hasil Diagnosis'),
    $historyOnly
        ? 'Satu hasil diagnosis yang dipilih dari riwayat konsultasi pengguna.'
        : (
            $currentOnly
                ? 'Satu hasil diagnosis terbaru yang baru saja dihasilkan dari proses konsultasi.'
                : 'Seluruh riwayat konsultasi pengguna dan hasil akhir perhitungan Certainty Factor.'
        ),
    count($rows),
    'hasil-diagnosis',
    $historyOnly
        ? ['scope' => 'history', 'riwayat_id' => $requestedHistoryId]
        : ($currentOnly ? ['scope' => 'current'] : [])
);
?>
<style>
    .diagnosis-report-table {
        table-layout: fixed;
    }

    .diagnosis-report-table .date-cell {
        text-align: center;
        white-space: nowrap;
    }

    .diagnosis-report-table .date-cell span {
        display: block;
    }

    .diagnosis-report-table .user-cell,
    .diagnosis-report-table .result-cell {
        overflow-wrap: anywhere;
    }

    .diagnosis-latin-name {
        display: block;
        margin-top: 4px;
        color: #4b5563;
        font-size: 10px;
        font-style: italic;
        line-height: 1.35;
    }

    .diagnosis-report-table .symptoms-cell {
        padding: 8px;
    }

    .symptom-count {
        display: inline-block;
        margin-bottom: 6px;
        color: #4b5563;
        font-size: 10px;
        font-weight: 600;
    }

    .symptom-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 5px 8px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .symptom-list li {
        display: grid;
        grid-template-columns: 43px minmax(0, 1fr);
        align-items: start;
        gap: 6px;
        min-width: 0;
        padding: 5px 6px;
        border: 1px solid #dbe4f0;
        border-radius: 5px;
        background: #f8fbff;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .symptom-code {
        display: inline-block;
        border-radius: 4px;
        background: #dbeafe;
        color: #0f2d5a;
        padding: 2px 4px;
        text-align: center;
        font-weight: 700;
        white-space: nowrap;
    }

    .symptom-name {
        min-width: 0;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .diagnosis-report-table .cf-cell {
        text-align: center;
        font-weight: 700;
        white-space: nowrap;
    }

    @media print {
        .diagnosis-report-table th,
        .diagnosis-report-table td {
            padding: 5px;
        }

        .diagnosis-report-table .symptoms-cell {
            padding: 5px;
        }

        .symptom-list {
            gap: 3px 5px;
        }

        .symptom-list li {
            grid-template-columns: 37px minmax(0, 1fr);
            gap: 4px;
            padding: 3px 4px;
        }

        .symptom-count {
            margin-bottom: 4px;
            font-size: 8px;
        }
    }

    @media (max-width: 900px) {
        .diagnosis-report-table {
            min-width: 1050px;
        }

        .symptom-list {
            grid-template-columns: 1fr;
        }
    }

    .report-recommendations {
        margin-top: 18px;
    }

    .report-recommendations > h3 {
        margin: 0 0 9px;
        color: #0f2d5a;
        font-size: 15px;
    }

    .report-treatment-card {
        margin-bottom: 12px;
        border: 1px solid #bfd2e8;
        border-radius: 8px;
        overflow: hidden;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .report-treatment-title {
        padding: 8px 11px;
        background: #edf5ff;
        color: #0f2d5a;
        font-size: 12px;
        font-weight: 700;
    }

    .report-treatment-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr);
    }

    .report-treatment-item {
        padding: 10px 11px;
    }

    .report-treatment-item + .report-treatment-item {
        border-left: 1px solid #dbe4f0;
        background: #f8fbff;
    }

    .report-treatment-item h4 {
        margin: 0 0 5px;
        color: #0f2d5a;
        font-size: 11px;
    }

    .report-treatment-item p {
        margin: 0;
        line-height: 1.5;
        white-space: pre-line;
    }

    .report-therapy-kind {
        display: block;
        margin-bottom: 3px;
        color: #047857;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .report-therapy-name {
        display: block;
        margin-bottom: 4px;
        color: #0f2d5a;
        font-size: 12px;
        font-weight: 700;
    }

    .report-treatment-warning {
        margin: 0;
        padding: 8px 11px;
        border-top: 1px solid #f3c66b;
        background: #fff8e8;
        color: #744500;
        line-height: 1.45;
    }

    .report-treatment-source {
        margin: 0;
        padding: 7px 11px;
        border-top: 1px solid #dbe4f0;
        color: #4b5563;
        font-size: 10px;
    }

    .report-treatment-source a {
        color: #075fbd;
        font-weight: 700;
    }

    @media print {
        .report-recommendations {
            margin-top: 12px;
        }

        .report-treatment-item {
            padding: 7px 8px;
        }

        .report-treatment-warning,
        .report-treatment-source {
            padding: 5px 8px;
        }
    }

    @media (max-width: 720px) {
        .report-treatment-grid {
            grid-template-columns: 1fr;
        }

        .report-treatment-item + .report-treatment-item {
            border-top: 1px solid #dbe4f0;
            border-left: 0;
        }
    }
</style>
<div class="table-wrap">
    <table class="diagnosis-report-table">
        <colgroup>
            <col style="width: 4%">
            <col style="width: 9%">
            <col style="width: 10%">
            <col style="width: 49%">
            <col style="width: 12%">
            <col style="width: 7%">
            <col style="width: 9%">
        </colgroup>
        <thead>
            <tr>
                <th class="number">No</th>
                <th>Tanggal</th>
                <th>Nama Pengguna</th>
                <th>Gejala Dipilih</th>
                <th>Hasil Diagnosis</th>
                <th>Nilai CF</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="empty"><?php echo $currentOnly ? 'Hasil diagnosis terkini tidak ditemukan.' : 'Belum ada riwayat diagnosis.'; ?></td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $index => $row): ?>
                <?php
                $selectedSymptoms = json_decode($row['detail_gejala'] ?? '[]', true);
                $symptomRows = [];
                if (is_array($selectedSymptoms)) {
                    foreach ($selectedSymptoms as $symptomId => $cfUser) {
                        if (isset($symptomMap[(string) $symptomId])) {
                            $symptom = $symptomMap[(string) $symptomId];
                            $symptomRows[] = [
                                'code' => $symptom['kode_gejala'],
                                'name' => $symptom['nama_gejala'],
                            ];
                        } else {
                            $symptomRows[] = [
                                'code' => '-',
                                'name' => 'Gejala basis pengetahuan lama',
                            ];
                        }
                    }
                }
                ?>
                <tr>
                    <td class="number"><?php echo $index + 1; ?></td>
                    <td class="date-cell">
                        <span><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></span>
                        <span><?php echo date('H:i', strtotime($row['tanggal'])); ?> WIB</span>
                    </td>
                    <td class="user-cell"><?php echo htmlspecialchars($row['nama']); ?></td>
                    <td class="symptoms-cell">
                        <?php if (empty($symptomRows)): ?>
                            <span class="empty">-</span>
                        <?php else: ?>
                            <span class="symptom-count"><?php echo count($symptomRows); ?> gejala dipilih</span>
                            <ol class="symptom-list">
                                <?php foreach ($symptomRows as $symptomRow): ?>
                                    <li>
                                        <span class="symptom-code"><?php echo htmlspecialchars($symptomRow['code']); ?></span>
                                        <span class="symptom-name"><?php echo htmlspecialchars($symptomRow['name']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </td>
                    <td class="result-cell">
                        <?php echo htmlspecialchars($row['penyakit_tertinggi'] ?? '-'); ?>
                        <?php if (trim((string) ($row['nama_latin'] ?? '')) !== ''): ?>
                            <span class="diagnosis-latin-name">
                                <?php echo htmlspecialchars($row['nama_latin'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="cf-cell"><?php echo number_format((float) $row['nilai_cf'], 3, ',', ''); ?></td>
                    <td class="cf-cell"><?php echo number_format((float) $row['nilai_cf'] * 100, 1, ',', ''); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (!empty($recommendations)): ?>
    <section class="report-recommendations" aria-labelledby="report-treatment-heading">
        <h3 id="report-treatment-heading">Rekomendasi Penanganan dan Terapi</h3>
        <?php foreach ($recommendations as $recommendation): ?>
            <article class="report-treatment-card">
                <div class="report-treatment-title">
                    Hasil diagnosis:
                    <?php echo htmlspecialchars($recommendation['diagnosis'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($recommendation['latin_name'] !== ''): ?>
                        <em>(<?php echo htmlspecialchars($recommendation['latin_name'], ENT_QUOTES, 'UTF-8'); ?>)</em>
                    <?php endif; ?>
                </div>
                <div class="report-treatment-grid">
                    <div class="report-treatment-item">
                        <h4>Penanganan awal</h4>
                        <p><?php echo htmlspecialchars(
                            $recommendation['solution'] !== ''
                                ? $recommendation['solution']
                                : 'Segera konsultasikan kondisi burung kepada dokter hewan.',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?></p>
                    </div>
                    <div class="report-treatment-item">
                        <span class="report-therapy-kind"><?php echo htmlspecialchars(
                            $recommendation['therapy_kind'],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?></span>
                        <span class="report-therapy-name"><?php echo htmlspecialchars(
                            $recommendation['therapy_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?></span>
                        <p><?php echo htmlspecialchars(
                            $recommendation['therapy_note'],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?></p>
                    </div>
                </div>
                <p class="report-treatment-warning">
                    <strong>Jangan memberikan obat sendiri.</strong>
                    Informasi terapi ini bukan resep. Dosis, rute, dan lama pemberian harus ditentukan dokter hewan sesuai spesies, berat badan, dan hasil pemeriksaan.
                </p>
                <?php if ($recommendation['source_title'] !== '' && $recommendation['source_url'] !== ''): ?>
                    <p class="report-treatment-source">
                        <strong>Sumber medis:</strong>
                        <a
                            href="<?php echo htmlspecialchars($recommendation['source_url'], ENT_QUOTES, 'UTF-8'); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        ><?php echo htmlspecialchars($recommendation['source_title'], ENT_QUOTES, 'UTF-8'); ?></a>
                        — diakses 29 Juli 2026.
                    </p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php renderReportEnd(); ?>
