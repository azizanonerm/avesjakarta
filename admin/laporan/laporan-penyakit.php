<?php
require_once '../includes/report-common.php';
requireAdminReportAccess();
require_once '../../config/database.php';

$rows = fetchAll(
    'SELECT kode_penyakit, nama_penyakit, deskripsi, solusi
     FROM penyakit ORDER BY kode_penyakit'
);

renderReportStart(
    'Laporan Data Penyakit',
    'Daftar seluruh penyakit kulit burung yang menjadi dasar proses diagnosis.',
    count($rows),
    'penyakit'
);
?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th class="number">No</th>
                <th>Kode Penyakit</th>
                <th>Nama Penyakit</th>
                <th>Deskripsi</th>
                <th>Saran Penanganan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td class="number"><?php echo $index + 1; ?></td>
                    <td class="code center"><?php echo htmlspecialchars($row['kode_penyakit']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_penyakit']); ?></td>
                    <td><?php echo htmlspecialchars($row['deskripsi'] ?? '-'); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($row['solusi'] ?? '-')); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php renderReportEnd(); ?>
