<?php
require_once '../includes/report-common.php';
requireAdminReportAccess();
require_once '../../config/database.php';

$rows = fetchAll(
    'SELECT kode_gejala, nama_gejala FROM gejala ORDER BY kode_gejala'
);

renderReportStart(
    'Laporan Data Gejala',
    'Daftar gejala penyakit kulit burung yang tersedia dalam proses konsultasi.',
    count($rows),
    'gejala'
);
?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th class="number">No</th>
                <th>Kode Gejala</th>
                <th>Nama Gejala</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td class="number"><?php echo $index + 1; ?></td>
                    <td class="code center"><?php echo htmlspecialchars($row['kode_gejala']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_gejala']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php renderReportEnd(); ?>
