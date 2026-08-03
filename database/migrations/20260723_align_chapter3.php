<?php
/**
 * Menyelaraskan basis pengetahuan dengan tabel pada Bab 3 skripsi:
 * 30 penyakit, 60 gejala, dan 120 relasi penyakit-gejala.
 *
 * Jalankan:
 * C:\xampp\php\php.exe database\migrations\20260723_align_chapter3.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Migrasi hanya dapat dijalankan melalui CLI.');
}

require_once __DIR__ . '/../../config/database.php';

$diseases = [
    ['P001', 'Infeksi Tungau', 'Infeksi Parasit'],
    ['P002', 'Infeksi Jamur', 'Infeksi Jamur'],
    ['P003', 'Dermatitis', 'Dermatitis'],
    ['P004', 'Infeksi Kutu Bulu', 'Infeksi Parasit'],
    ['P005', 'Infeksi Bakteri pada Kulit', 'Infeksi Bakteri'],
    ['P006', 'Scaly Face Mite', 'Infeksi Tungau'],
    ['P007', 'Scaly Leg Mite', 'Infeksi Tungau'],
    ['P008', 'Cacar Burung', 'Infeksi Virus'],
    ['P009', 'Bumblefoot / Pododermatitis', 'Pododermatitis'],
    ['P010', 'Folikulitis', 'Infeksi Bakteri'],
    ['P011', 'Feather Cyst', 'Gangguan Bulu'],
    ['P012', 'Feather Plucking', 'Gangguan Perilaku'],
    ['P013', 'Infeksi Luka Kulit', 'Infeksi Bakteri'],
    ['P014', 'Abses Kulit', 'Infeksi Bakteri'],
    ['P015', 'Kulit Kering dan Bersisik', 'Dermatitis'],
    ['P016', 'Iritasi Kulit akibat Lingkungan', 'Dermatitis'],
    ['P017', 'Alergi atau Reaksi Iritatif', 'Dermatitis'],
    ['P018', 'Infeksi Paruh akibat Tungau', 'Infeksi Tungau'],
    ['P019', 'Lesi pada Kelopak Mata', 'Lesi Kulit'],
    ['P020', 'Infeksi Kulit Sekunder', 'Infeksi Bakteri'],
    ['P021', 'Nekrosis Kulit', 'Nekrosis Kulit'],
    ['P022', 'Hiperkeratosis', 'Dermatitis'],
    ['P023', 'Kerontokan Bulu Lokal', 'Gangguan Bulu'],
    ['P024', 'Kerusakan Bulu akibat Parasit', 'Infeksi Parasit'],
    ['P025', 'Radang Kulit pada Sayap', 'Dermatitis'],
    ['P026', 'Radang Kulit pada Kaki', 'Dermatitis'],
    ['P027', 'Luka Gesek pada Telapak Kaki', 'Trauma Kulit'],
    ['P028', 'Infeksi Kulit Bernanah', 'Infeksi Bakteri'],
    ['P029', 'Gangguan Kulit akibat Kandang Lembap', 'Dermatitis'],
    ['P030', 'Gangguan Kulit akibat Kekurangan Nutrisi', 'Gangguan Nutrisi'],
];

$diseaseDescriptions = require __DIR__ . '/../data/disease-descriptions.php';
$diseaseSolutions = require __DIR__ . '/../data/disease-solutions.php';
$diseaseLatinNames = require __DIR__ . '/../data/disease-latin-names.php';

$symptoms = [
    ['G001', 'Burung sering menggaruk tubuh', 'Perilaku'],
    ['G002', 'Burung sering mematuk bagian tubuh tertentu', 'Perilaku'],
    ['G003', 'Kulit tampak kemerahan', 'Kulit dan Lesi'],
    ['G004', 'Kulit tampak iritasi', 'Kulit dan Lesi'],
    ['G005', 'Kulit tampak kering', 'Kulit dan Lesi'],
    ['G006', 'Kulit tampak bersisik', 'Kulit dan Lesi'],
    ['G007', 'Muncul kerak pada kulit', 'Kulit dan Lesi'],
    ['G008', 'Muncul kerak pada kaki', 'Paruh dan Kaki'],
    ['G009', 'Muncul kerak pada paruh', 'Paruh dan Kaki'],
    ['G010', 'Muncul kerak di sekitar mata', 'Mata dan Wajah'],
    ['G011', 'Bulu rontok', 'Bulu'],
    ['G012', 'Bulu tampak rusak', 'Bulu'],
    ['G013', 'Bulu tampak kusam', 'Bulu'],
    ['G014', 'Terdapat bercak pada kulit', 'Kulit dan Lesi'],
    ['G015', 'Terdapat luka pada kulit', 'Luka Kulit'],
    ['G016', 'Luka tampak basah', 'Luka Kulit'],
    ['G017', 'Luka mengeluarkan cairan', 'Luka Kulit'],
    ['G018', 'Luka tampak bernanah', 'Luka Kulit'],
    ['G019', 'Terdapat pembengkakan pada kulit', 'Kulit dan Lesi'],
    ['G020', 'Terdapat benjolan pada kulit', 'Kulit dan Lesi'],
    ['G021', 'Terdapat benjolan pada folikel bulu', 'Bulu'],
    ['G022', 'Muncul nodul atau tonjolan kecil', 'Kulit dan Lesi'],
    ['G023', 'Kulit terasa menebal', 'Kulit dan Lesi'],
    ['G024', 'Kulit tampak mengelupas', 'Kulit dan Lesi'],
    ['G025', 'Area kulit tampak botak atau alopesia', 'Bulu'],
    ['G026', 'Paruh tampak kasar', 'Paruh dan Kaki'],
    ['G027', 'Paruh tampak menebal', 'Paruh dan Kaki'],
    ['G028', 'Kaki tampak kasar', 'Paruh dan Kaki'],
    ['G029', 'Sisik kaki tampak terangkat', 'Paruh dan Kaki'],
    ['G030', 'Telapak kaki tampak kemerahan', 'Paruh dan Kaki'],
    ['G031', 'Telapak kaki tampak bengkak', 'Paruh dan Kaki'],
    ['G032', 'Telapak kaki terdapat luka', 'Paruh dan Kaki'],
    ['G033', 'Burung tampak pincang', 'Paruh dan Kaki'],
    ['G034', 'Burung enggan bertengger', 'Paruh dan Kaki'],
    ['G035', 'Burung tampak gelisah', 'Kondisi Umum'],
    ['G036', 'Burung sering membersihkan bulu secara berlebihan', 'Perilaku'],
    ['G037', 'Burung mencabuti bulunya sendiri', 'Perilaku'],
    ['G038', 'Bulu patah atau tidak tumbuh normal', 'Bulu'],
    ['G039', 'Pertumbuhan bulu terganggu', 'Bulu'],
    ['G040', 'Terdapat parasit kecil pada bulu atau kulit', 'Parasit Terlihat'],
    ['G041', 'Kulit tampak lembap berlebihan', 'Kulit dan Lesi'],
    ['G042', 'Kandang dalam kondisi lembap', 'Lingkungan'],
    ['G043', 'Timbul bau tidak sedap pada luka', 'Luka Kulit'],
    ['G044', 'Nafsu makan menurun', 'Kondisi Umum'],
    ['G045', 'Burung tampak lemas', 'Kondisi Umum'],
    ['G046', 'Burung tampak kurang aktif', 'Kondisi Umum'],
    ['G047', 'Berat badan menurun', 'Kondisi Umum'],
    ['G048', 'Muncul bintil pada kulit tanpa bulu', 'Kulit dan Lesi'],
    ['G049', 'Muncul keropeng atau scab', 'Kulit dan Lesi'],
    ['G050', 'Lesi muncul pada area wajah', 'Mata dan Wajah'],
    ['G051', 'Lesi muncul pada kelopak mata', 'Mata dan Wajah'],
    ['G052', 'Mata tampak terganggu akibat lesi sekitar kulit', 'Mata dan Wajah'],
    ['G053', 'Sayap tampak mengalami luka atau iritasi', 'Sayap'],
    ['G054', 'Kulit pada sayap tampak meradang', 'Sayap'],
    ['G055', 'Kulit tampak kehitaman', 'Kulit dan Lesi'],
    ['G056', 'Jaringan kulit tampak mati atau rusak', 'Kulit dan Lesi'],
    ['G057', 'Kulit mudah terluka', 'Kulit dan Lesi'],
    ['G058', 'Luka sulit sembuh', 'Luka Kulit'],
    ['G059', 'Area kulit terasa panas atau meradang', 'Kulit dan Lesi'],
    ['G060', 'Kebersihan kandang kurang terjaga', 'Lingkungan'],
];

$rules = [
    'P001' => [['G001', 0.8], ['G002', 0.7], ['G007', 0.8], ['G040', 0.9]],
    'P002' => [['G005', 0.7], ['G006', 0.8], ['G011', 0.7], ['G014', 0.8]],
    'P003' => [['G003', 0.8], ['G004', 0.8], ['G015', 0.6], ['G059', 0.7]],
    'P004' => [['G001', 0.7], ['G011', 0.8], ['G012', 0.9], ['G040', 0.9]],
    'P005' => [['G003', 0.7], ['G015', 0.8], ['G018', 0.9], ['G043', 0.8]],
    'P006' => [['G009', 0.9], ['G010', 0.8], ['G023', 0.7], ['G050', 0.8]],
    'P007' => [['G008', 0.9], ['G028', 0.8], ['G029', 0.9], ['G023', 0.7]],
    'P008' => [['G048', 0.9], ['G049', 0.8], ['G050', 0.8], ['G051', 0.7]],
    'P009' => [['G030', 0.8], ['G031', 0.9], ['G032', 0.9], ['G033', 0.8]],
    'P010' => [['G021', 0.8], ['G039', 0.7], ['G019', 0.7], ['G025', 0.8]],
    'P011' => [['G020', 0.8], ['G021', 0.9], ['G038', 0.8], ['G039', 0.7]],
    'P012' => [['G036', 0.8], ['G037', 0.9], ['G025', 0.8], ['G035', 0.7]],
    'P013' => [['G015', 0.9], ['G016', 0.8], ['G017', 0.8], ['G058', 0.7]],
    'P014' => [['G018', 0.9], ['G019', 0.8], ['G020', 0.9], ['G043', 0.8]],
    'P015' => [['G005', 0.9], ['G006', 0.8], ['G024', 0.7], ['G057', 0.6]],
    'P016' => [['G003', 0.7], ['G004', 0.8], ['G060', 0.7], ['G041', 0.6]],
    'P017' => [['G003', 0.7], ['G004', 0.8], ['G014', 0.6], ['G035', 0.7]],
    'P018' => [['G009', 0.9], ['G026', 0.8], ['G027', 0.8], ['G050', 0.7]],
    'P019' => [['G010', 0.7], ['G051', 0.9], ['G052', 0.8], ['G049', 0.7]],
    'P020' => [['G015', 0.8], ['G018', 0.9], ['G043', 0.8], ['G058', 0.8]],
    'P021' => [['G055', 0.9], ['G056', 0.9], ['G015', 0.8], ['G045', 0.7]],
    'P022' => [['G023', 0.9], ['G024', 0.7], ['G027', 0.8], ['G029', 0.8]],
    'P023' => [['G011', 0.9], ['G025', 0.8], ['G038', 0.7], ['G039', 0.8]],
    'P024' => [['G012', 0.9], ['G013', 0.7], ['G036', 0.8], ['G040', 0.9]],
    'P025' => [['G053', 0.8], ['G054', 0.9], ['G003', 0.7], ['G015', 0.7]],
    'P026' => [['G028', 0.7], ['G030', 0.8], ['G031', 0.8], ['G033', 0.7]],
    'P027' => [['G032', 0.9], ['G034', 0.8], ['G058', 0.7], ['G030', 0.8]],
    'P028' => [['G018', 0.9], ['G043', 0.8], ['G059', 0.8], ['G045', 0.7]],
    'P029' => [['G041', 0.8], ['G042', 0.9], ['G005', 0.6], ['G006', 0.7]],
    'P030' => [['G013', 0.8], ['G039', 0.7], ['G044', 0.7], ['G047', 0.8]],
];

if (count($diseases) !== 30 || count($symptoms) !== 60) {
    throw new RuntimeException('Jumlah data master Bab 3 tidak sesuai.');
}

$ruleCount = array_sum(array_map('count', $rules));
if ($ruleCount !== 120) {
    throw new RuntimeException('Jumlah relasi CF Bab 3 harus 120.');
}

$diseaseCodes = array_column($diseases, 0);
$symptomCodes = array_column($symptoms, 0);
foreach ($rules as $diseaseCode => $diseaseRules) {
    if (!in_array($diseaseCode, $diseaseCodes, true)) {
        throw new RuntimeException("Kode penyakit $diseaseCode tidak ditemukan.");
    }
    foreach ($diseaseRules as [$symptomCode]) {
        if (!in_array($symptomCode, $symptomCodes, true)) {
            throw new RuntimeException("Kode gejala $symptomCode tidak ditemukan.");
        }
    }
}

try {
    $pdo->beginTransaction();

    // Riwayat tidak memiliki foreign key ke tabel ini, sehingga laporan lama
    // tetap tersimpan meskipun basis pengetahuan aktif diganti.
    $pdo->exec('DELETE FROM aturan');
    $pdo->exec('DELETE FROM penyakit');
    $pdo->exec('DELETE FROM gejala');

    $diseaseStmt = $pdo->prepare(
        'INSERT INTO penyakit
         (kode_penyakit, nama_penyakit, nama_latin, kategori, deskripsi, solusi, sumber_referensi, status_validasi, is_unggulan)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($diseases as [$code, $name, $category]) {
        $description = $diseaseDescriptions[$code];
        $solution = $diseaseSolutions[$code];
        $diseaseStmt->execute([
            $code,
            $name,
            $diseaseLatinNames[$code] ?? null,
            $category,
            $description,
            $solution,
            'Bab 3 skripsi',
            'sesuai_bab_3',
            in_array($code, ['P001', 'P002', 'P003', 'P004', 'P005'], true) ? 1 : 0,
        ]);
    }

    $symptomStmt = $pdo->prepare(
        'INSERT INTO gejala (kode_gejala, nama_gejala, kategori, deskripsi)
         VALUES (?, ?, ?, ?)'
    );
    foreach ($symptoms as [$code, $name, $category]) {
        $symptomStmt->execute([$code, $name, $category, $name . '.']);
    }

    $diseaseIds = [];
    foreach ($pdo->query('SELECT id_penyakit, kode_penyakit FROM penyakit') as $row) {
        $diseaseIds[$row['kode_penyakit']] = (int) $row['id_penyakit'];
    }

    $symptomIds = [];
    foreach ($pdo->query('SELECT id_gejala, kode_gejala FROM gejala') as $row) {
        $symptomIds[$row['kode_gejala']] = (int) $row['id_gejala'];
    }

    $ruleStmt = $pdo->prepare(
        'INSERT INTO aturan (id_penyakit, id_gejala, cf_pakar) VALUES (?, ?, ?)'
    );
    foreach ($rules as $diseaseCode => $diseaseRules) {
        foreach ($diseaseRules as [$symptomCode, $weight]) {
            $ruleStmt->execute([
                $diseaseIds[$diseaseCode],
                $symptomIds[$symptomCode],
                $weight,
            ]);
        }
    }

    $pdo->commit();

    $counts = [
        'penyakit' => (int) $pdo->query('SELECT COUNT(*) FROM penyakit')->fetchColumn(),
        'gejala' => (int) $pdo->query('SELECT COUNT(*) FROM gejala')->fetchColumn(),
        'aturan_cf' => (int) $pdo->query('SELECT COUNT(*) FROM aturan')->fetchColumn(),
    ];

    if ($counts !== ['penyakit' => 30, 'gejala' => 60, 'aturan_cf' => 120]) {
        throw new RuntimeException('Verifikasi jumlah data setelah migrasi gagal.');
    }

    echo 'Migrasi Bab 3 berhasil: ' . json_encode($counts, JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Migrasi Bab 3 gagal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
