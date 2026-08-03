<?php
/**
 * Memperbarui deskripsi seluruh penyakit pada basis pengetahuan aktif.
 *
 * Jalankan:
 * C:\xampp\php\php.exe database\migrations\20260724_update_disease_descriptions.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Migrasi hanya dapat dijalankan melalui CLI.');
}

require_once __DIR__ . '/../../config/database.php';

$diseaseDescriptions = require __DIR__ . '/../data/disease-descriptions.php';
$updateStmt = $pdo->prepare(
    'UPDATE penyakit
     SET deskripsi = ?
     WHERE kode_penyakit = ?'
);

$pdo->beginTransaction();

try {
    $updated = 0;

    foreach ($diseaseDescriptions as $code => $description) {
        $updateStmt->execute([$description, $code]);

        if ($updateStmt->rowCount() > 0) {
            $updated++;
        }
    }

    $pdo->commit();

    echo "Deskripsi penyakit berhasil diperbarui: {$updated} dari "
        . count($diseaseDescriptions)
        . " data.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Migrasi gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
