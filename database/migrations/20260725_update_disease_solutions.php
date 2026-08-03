<?php
/**
 * Memperbarui saran penanganan seluruh penyakit pada basis pengetahuan aktif.
 *
 * Jalankan:
 * C:\xampp\php\php.exe database\migrations\20260725_update_disease_solutions.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Migrasi hanya dapat dijalankan melalui CLI.');
}

require_once __DIR__ . '/../../config/database.php';

$diseaseSolutions = require __DIR__ . '/../data/disease-solutions.php';
$updateStmt = $pdo->prepare(
    'UPDATE penyakit
     SET solusi = ?
     WHERE kode_penyakit = ?'
);

$pdo->beginTransaction();

try {
    $matched = 0;

    foreach ($diseaseSolutions as $code => $solution) {
        $updateStmt->execute([$solution, $code]);

        if ($updateStmt->rowCount() > 0) {
            $matched++;
        }
    }

    $pdo->commit();

    echo "Saran penanganan berhasil diperbarui: {$matched} dari "
        . count($diseaseSolutions)
        . " data.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, 'Migrasi gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
