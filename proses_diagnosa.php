<?php
/**
 * Proses Diagnosa Backend
 * Menghitung Certainty Factor dan menyimpan hasil
 */

require_once __DIR__ . '/config/security.php';

// Cek login
requireRegularUser('login.php', 'admin/dashboard.php');
requirePostRequest();
verifyCsrfOrAbort();

require_once 'config/database.php';
require_once 'config/certainty.php';
require_once 'lib/CertaintyFactor.php';

// Ambil data dari form
$selected_gejala = $_POST['gejala'] ?? [];
$cf_values = $_POST['cf'] ?? [];

if (!is_array($selected_gejala) || !is_array($cf_values) || count($selected_gejala) > 60) {
    $_SESSION['error'] = 'Data gejala tidak valid.';
    header('Location: konsultasi.php');
    exit;
}

// Validasi: minimal ada 1 gejala yang dipilih
if (empty($selected_gejala)) {
    $_SESSION['error'] = 'Silakan pilih minimal satu gejala';
    header('Location: konsultasi.php');
    exit;
}

// Siapkan array gejala dengan CF user
$symptoms_with_cf = [];
foreach ($selected_gejala as $symptom_id => $value) {
    if (!ctype_digit((string) $symptom_id) || (int) $symptom_id <= 0) {
        $_SESSION['error'] = 'Data gejala tidak valid.';
        header('Location: konsultasi.php');
        exit;
    }

    if (!array_key_exists($symptom_id, $cf_values)) {
        $_SESSION['error'] = 'Pilih tingkat keyakinan untuk setiap gejala yang dicentang.';
        header('Location: konsultasi.php');
        exit;
    }

    $cf_user = normalizeUserCertainty($cf_values[$symptom_id]);

    if ($cf_user === null) {
        $_SESSION['error'] = 'Nilai keyakinan tidak valid. Gunakan pilihan CF 0,0; 0,2; 0,4; 0,6; 0,8; atau 1,0.';
        header('Location: konsultasi.php');
        exit;
    }

    $symptoms_with_cf[(int) $symptom_id] = $cf_user;
}

if (max($symptoms_with_cf) <= 0.0) {
    $_SESSION['error'] = 'Minimal satu gejala harus memiliki tingkat keyakinan di atas Tidak Yakin (0,0).';
    header('Location: konsultasi.php');
    exit;
}

// Jalankan diagnosa menggunakan Certainty Factor
$diagnosis_results = CertaintyFactor::diagnose($symptoms_with_cf, $pdo);

// Ambil hasil tertinggi
$top_diagnosis = $diagnosis_results[0] ?? null;

if (!$top_diagnosis) {
    $_SESSION['error'] = 'Tidak dapat melakukan diagnosa. Silakan coba lagi.';
    header('Location: konsultasi.php');
    exit;
}

// Simpan ke database riwayat
$detail_gejala = json_encode($symptoms_with_cf);
// Simpan hanya satu hasil akhir dengan nilai CF tertinggi.
$final_result = [$top_diagnosis];
$detail_hasil = json_encode($final_result);

try {
    execute(
        'INSERT INTO riwayat (id_user, penyakit_tertinggi, nilai_cf, detail_gejala, detail_hasil) 
         VALUES (?, ?, ?, ?, ?)',
        [
            $_SESSION['user_id'],
            $top_diagnosis['nama_penyakit'],
            $top_diagnosis['cf_final'],
            $detail_gejala,
            $detail_hasil
        ]
    );
    $latest_diagnosis_id = (int) $pdo->lastInsertId();
    
    // Simpan hasil ke session untuk ditampilkan di hasil_diagnosa.php
    $_SESSION['top_diagnosis'] = $top_diagnosis;
    $_SESSION['symptoms_with_cf'] = $symptoms_with_cf;
    $_SESSION['latest_diagnosis_id'] = $latest_diagnosis_id;
    
    // Redirect ke halaman hasil
    header('Location: hasil_diagnosa.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Terjadi kesalahan saat menyimpan hasil diagnosa';
    header('Location: konsultasi.php');
    exit;
}
?>
