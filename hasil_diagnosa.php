<?php
/**
 * Pengarah hasil diagnosis terbaru ke tampilan detail bersama.
 * Dengan satu tampilan, hasil baru dan riwayat pengguna selalu konsisten.
 */

require_once __DIR__ . '/config/security.php';
requireRegularUser('login.php', 'admin/dashboard.php');
require_once __DIR__ . '/config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$diagnosisId = (int) ($_SESSION['latest_diagnosis_id'] ?? 0);
$diagnosis = null;

if ($diagnosisId > 0) {
    $diagnosis = fetchOne(
        'SELECT id_riwayat FROM riwayat WHERE id_riwayat = ? AND id_user = ?',
        [$diagnosisId, $userId]
    );
}

if (!$diagnosis) {
    $diagnosis = fetchOne(
        'SELECT id_riwayat
         FROM riwayat
         WHERE id_user = ?
         ORDER BY tanggal DESC, id_riwayat DESC
         LIMIT 1',
        [$userId]
    );
}

$diagnosisId = (int) ($diagnosis['id_riwayat'] ?? 0);
if ($diagnosisId <= 0) {
    header('Location: konsultasi.php');
    exit;
}

$_SESSION['latest_diagnosis_id'] = $diagnosisId;
unset($_SESSION['diagnosis_results']);

header('Location: detail-riwayat.php?id=' . $diagnosisId . '&source=current');
exit;
