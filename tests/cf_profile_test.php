<?php
/** Uji profil seluruh penyakit pada basis pengetahuan Bab 3. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/CertaintyFactor.php';

$counts = [
    'penyakit' => (int) fetchOne('SELECT COUNT(*) AS total FROM penyakit')['total'],
    'gejala' => (int) fetchOne('SELECT COUNT(*) AS total FROM gejala')['total'],
    'aturan' => (int) fetchOne('SELECT COUNT(*) AS total FROM aturan')['total'],
];

$expectedCounts = ['penyakit' => 30, 'gejala' => 60, 'aturan' => 120];
$failed = $counts !== $expectedCounts;
echo sprintf(
    "[%s] Jumlah data: penyakit=%d gejala=%d aturan=%d\n",
    $failed ? 'GAGAL' : 'OK',
    $counts['penyakit'],
    $counts['gejala'],
    $counts['aturan']
);

$diseases = fetchAll('SELECT id_penyakit, kode_penyakit, nama_penyakit FROM penyakit ORDER BY kode_penyakit');
foreach ($diseases as $disease) {
    $rules = fetchAll(
        'SELECT id_gejala FROM aturan WHERE id_penyakit = ? ORDER BY id_aturan',
        [$disease['id_penyakit']]
    );

    if (count($rules) !== 4) {
        echo sprintf(
            "[GAGAL] %s memiliki %d relasi, seharusnya 4\n",
            $disease['kode_penyakit'],
            count($rules)
        );
        $failed = true;
        continue;
    }

    $selected = [];
    $expectedCf = 0.0;
    foreach ($rules as $rule) {
        $selected[(int) $rule['id_gejala']] = 1.0;
    }

    $results = CertaintyFactor::diagnose($selected, $pdo);
    $diseaseResult = null;
    foreach ($results as $result) {
        if ($result['kode_penyakit'] === $disease['kode_penyakit']) {
            $diseaseResult = $result;
            break;
        }
    }

    $weights = fetchAll(
        'SELECT cf_pakar FROM aturan WHERE id_penyakit = ? ORDER BY id_aturan',
        [$disease['id_penyakit']]
    );
    foreach ($weights as $index => $weight) {
        $value = (float) $weight['cf_pakar'];
        $expectedCf = $index === 0
            ? $value
            : CertaintyFactor::combineCF($expectedCf, $value);
    }
    $expectedCf = round($expectedCf, 3);

    $actualCf = $diseaseResult['cf_final'] ?? null;
    $passed = $actualCf !== null && abs($actualCf - $expectedCf) < 0.00001;
    $failed = $failed || !$passed;

    echo sprintf(
        "[%s] %s - %s: CF harapan=%s aktual=%s\n",
        $passed ? 'OK' : 'GAGAL',
        $disease['kode_penyakit'],
        $disease['nama_penyakit'],
        $expectedCf,
        $actualCf ?? '-'
    );
}

exit($failed ? 1 : 0);
