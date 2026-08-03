<?php
require_once '../includes/report-common.php';
requireAdminReportAccess();
require_once '../../config/database.php';

$rows = fetchAll(
    'SELECT p.kode_penyakit, p.nama_penyakit,
            g.kode_gejala, g.nama_gejala, a.cf_pakar
     FROM aturan a
     JOIN penyakit p ON p.id_penyakit = a.id_penyakit
     JOIN gejala g ON g.id_gejala = a.id_gejala
     ORDER BY p.kode_penyakit, g.kode_gejala'
);

renderReportStart(
    'Laporan Basis Pengetahuan / Rule Base',
    'Hubungan penyakit, gejala, dan nilai Certainty Factor yang diberikan pakar.',
    count($rows),
    'rule-base'
);
?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th class="number">No</th>
                <th>Kode Penyakit</th>
                <th>Nama Penyakit</th>
                <th>Kode Gejala</th>
                <th>Nama Gejala</th>
                <th>CF Pakar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td class="number"><?php echo $index + 1; ?></td>
                    <td class="code center"><?php echo htmlspecialchars($row['kode_penyakit']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_penyakit']); ?></td>
                    <td class="code center"><?php echo htmlspecialchars($row['kode_gejala']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_gejala']); ?></td>
                    <td class="center"><?php echo number_format((float) $row['cf_pakar'], 1, ',', ''); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php renderReportEnd(); ?>
