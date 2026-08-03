<?php
/**
 * Skala Certainty Factor sesuai tabel pada skripsi.
 *
 * Skala pilihan pengguna harus diskrit. Nilai di luar daftar ini tidak boleh
 * masuk ke proses perhitungan diagnosis.
 */

function getUserCertaintyOptions(): array
{
    return [
        ['value' => '0.0', 'label' => 'Tidak Yakin'],
        ['value' => '0.2', 'label' => 'Kurang Yakin'],
        ['value' => '0.4', 'label' => 'Sedikit Yakin'],
        ['value' => '0.6', 'label' => 'Cukup Yakin'],
        ['value' => '0.8', 'label' => 'Yakin'],
        ['value' => '1.0', 'label' => 'Sangat Yakin'],
    ];
}

function normalizeUserCertainty($value): ?float
{
    if (!is_numeric($value)) {
        return null;
    }

    $numericValue = (float) $value;
    $allowedValues = [0.0, 0.2, 0.4, 0.6, 0.8, 1.0];

    foreach ($allowedValues as $allowedValue) {
        if (abs($numericValue - $allowedValue) < 0.00001) {
            return $allowedValue;
        }
    }

    return null;
}

function getUserCertaintyLabel($value): string
{
    $normalizedValue = normalizeUserCertainty($value);

    if ($normalizedValue === null) {
        return 'Tidak Diketahui';
    }

    foreach (getUserCertaintyOptions() as $option) {
        if (abs((float) $option['value'] - $normalizedValue) < 0.00001) {
            return $option['label'];
        }
    }

    return 'Tidak Diketahui';
}

/**
 * Interpretasi nilai akhir CF sesuai rentang pada skripsi.
 */
function getCertaintyInterpretation($cf): string
{
    $cf = max(0.0, min(1.0, (float) $cf));

    if ($cf <= 0.20) {
        return 'Tidak Tahu / Sangat Rendah';
    }

    if ($cf <= 0.40) {
        return 'Kemungkinan Kecil';
    }

    if ($cf <= 0.60) {
        return 'Kemungkinan Sedang';
    }

    if ($cf <= 0.80) {
        return 'Kemungkinan Besar';
    }

    if ($cf < 1.0) {
        return 'Hampir Pasti';
    }

    return 'Pasti';
}

