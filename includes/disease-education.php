<?php

/**
 * Mengambil materi edukasi visual untuk satu kode penyakit.
 */
function getDiseaseEducation(string $diseaseCode): ?array
{
    static $educationData = null;

    if ($educationData === null) {
        $educationData = require __DIR__ . '/../database/data/disease-education.php';
    }

    $code = strtoupper(trim($diseaseCode));
    if (!isset($educationData[$code])) {
        return null;
    }

    $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $applicationBase = str_ends_with($scriptDirectory, '/admin')
        ? dirname($scriptDirectory)
        : $scriptDirectory;
    $applicationBase = rtrim(str_replace('\\', '/', $applicationBase), '/');

    $standaloneDiseaseImages = [
        'P023' => $applicationBase . '/assets/education/disease-p023-v3.png',
    ];

    $index = (int) substr($code, 1) - 1;
    if ($index < 0 || $index > 29) {
        return null;
    }

    /*
     * Batas tile asli pada atlas. Atlas memiliki gutter putih dan tinggi baris
     * yang tidak seragam, sehingga persentase 20%/25% dapat mengambil serpihan
     * tile tetangga.
     */
    $diseaseColumns = [
        [7, 254],
        [262, 509],
        [516, 764],
        [771, 1019],
        [1026, 1273],
        [1280, 1528],
    ];
    $diseaseRows = [
        [7, 217],
        [224, 419],
        [426, 621],
        [627, 805],
        [813, 1015],
    ];
    $columnIndex = $index % 6;
    $rowIndex = intdiv($index, 6);
    [$tileLeft, $tileRight] = $diseaseColumns[$columnIndex];
    [$tileTop, $tileBottom] = $diseaseRows[$rowIndex];
    $tileWidth = $tileRight - $tileLeft + 1;
    $tileHeight = $tileBottom - $tileTop + 1;
    $atlasWidth = 1536;
    $atlasHeight = 1024;

    return $educationData[$code] + [
        'code' => $code,
        'disease_tile_x' => ($tileLeft / ($atlasWidth - $tileWidth)) * 100,
        'disease_tile_y' => ($tileTop / ($atlasHeight - $tileHeight)) * 100,
        'disease_size_x' => ($atlasWidth / $tileWidth) * 100,
        'disease_size_y' => ($atlasHeight / $tileHeight) * 100,
        'disease_image_url' => $standaloneDiseaseImages[$code] ?? '',
        'medicine_tile_x' => ((int) $educationData[$code]['medicine_tile'] % 3) * 50,
        'medicine_tile_y' => intdiv((int) $educationData[$code]['medicine_tile'], 3) * 100,
    ];
}

/**
 * Menampilkan kartu visualisasi penyakit, terapi, peringatan, dan sumber.
 */
function renderDiseaseEducation(string $diseaseCode, string $diseaseName = ''): void
{
    $education = getDiseaseEducation($diseaseCode);
    if ($education === null) {
        return;
    }

    $escape = static fn (string $value): string => htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $title = $diseaseName !== '' ? $diseaseName : $education['code'];
    $sectionId = 'edukasi-' . strtolower($education['code']);
    $hasStandaloneDiseaseImage = ($education['disease_image_url'] ?? '') !== '';
    $diseaseStyle = $hasStandaloneDiseaseImage
        ? sprintf('background-image:url("%s")', $education['disease_image_url'])
        : sprintf(
            '--sprite-x:%.5F%%;--sprite-y:%.5F%%;--sprite-size-x:%.5F%%;--sprite-size-y:%.5F%%',
            $education['disease_tile_x'],
            $education['disease_tile_y'],
            $education['disease_size_x'],
            $education['disease_size_y']
        );
    $medicineStyle = sprintf(
        '--sprite-x:%d%%;--sprite-y:%d%%',
        $education['medicine_tile_x'],
        $education['medicine_tile_y']
    );
    ?>
    <section class="disease-education" aria-labelledby="<?php echo $escape($sectionId); ?>">
        <div class="disease-education__heading">
            <span class="disease-education__eyebrow">Informasi visual dan terapi</span>
            <h3 id="<?php echo $escape($sectionId); ?>">
                Visualisasi <?php echo $escape($title); ?>
            </h3>
            <p>Ilustrasi membantu mengenali gambaran umum, tetapi tidak dapat menggantikan pemeriksaan dokter hewan.</p>
        </div>

        <div class="disease-education__grid">
            <article class="education-card">
                <div
                    class="education-card__image education-card__image--disease<?php echo $hasStandaloneDiseaseImage ? ' education-card__image--standalone' : ''; ?>"
                    style="<?php echo $escape($diseaseStyle); ?>"
                    role="img"
                    aria-label="<?php echo $escape('Ilustrasi edukasi ' . $title); ?>"
                ></div>
                <div class="education-card__body">
                    <span class="education-card__label">Ilustrasi kondisi</span>
                    <h4><?php echo $escape($title); ?></h4>
                    <p><?php echo $escape($education['visual_caption']); ?></p>
                </div>
            </article>

            <article class="education-card education-card--therapy">
                <div
                    class="education-card__image education-card__image--medicine"
                    style="<?php echo $escape($medicineStyle); ?>"
                    role="img"
                    aria-label="<?php echo $escape('Ilustrasi bentuk terapi: ' . $education['therapy_name']); ?>"
                ></div>
                <div class="education-card__body">
                    <span class="education-card__label"><?php echo $escape($education['therapy_kind']); ?></span>
                    <h4><?php echo $escape($education['therapy_name']); ?></h4>
                    <p><?php echo $escape($education['therapy_note']); ?></p>
                </div>
            </article>
        </div>

        <div class="disease-education__warning" role="note">
            <span class="material-symbols-outlined" aria-hidden="true">warning</span>
            <p>
                <strong>Jangan memberikan obat sendiri.</strong>
                Nama terapi di atas merupakan informasi edukasi, bukan resep. Dosis, rute, dan lama pemberian harus ditentukan dokter hewan sesuai spesies, berat badan, dan hasil pemeriksaan.
            </p>
        </div>

        <div class="disease-education__source">
            <div>
                <span class="material-symbols-outlined" aria-hidden="true">verified</span>
                <p>
                    <strong>Sumber medis:</strong>
                    <a
                        href="<?php echo $escape($education['source']['url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    ><?php echo $escape($education['source']['title']); ?></a>
                    <span class="disease-education__accessed">Diakses 29 Juli 2026.</span>
                </p>
            </div>
            <p class="disease-education__image-note">
                Visual merupakan ilustrasi edukasi buatan AI berdasarkan uraian referensi, bukan foto pasien atau bukti diagnosis.
            </p>
        </div>
    </section>
    <?php
}
