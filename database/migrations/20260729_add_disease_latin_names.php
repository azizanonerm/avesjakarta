<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Migrasi hanya dapat dijalankan melalui CLI.');
}

require_once __DIR__ . '/../../config/database.php';

$latinNames = require __DIR__ . '/../data/disease-latin-names.php';
$updateDisease = $pdo->prepare(
    'UPDATE penyakit SET nama_latin = ? WHERE kode_penyakit = ?'
);

$pdo->beginTransaction();

try {
    $updated = 0;

    foreach ($latinNames as $code => $latinName) {
        $updateDisease->execute([$latinName, $code]);
        $updated += $updateDisease->rowCount();
    }

    $pdo->commit();
    echo 'Nama Latin penyakit berhasil diperbarui: ' . $updated . PHP_EOL;
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $error;
}
