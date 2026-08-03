<?php
/**
 * Detail Riwayat Diagnosis Pengguna
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/config/security.php';
requireRegularUser('login.php', 'admin/dashboard.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/certainty.php';
require_once __DIR__ . '/includes/disease-education.php';

$riwayatId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$isCurrentResult = (string) ($_GET['source'] ?? '') === 'current';
$riwayat = fetchOne(
    'SELECT r.*, u.nama, u.email
     FROM riwayat r
     JOIN users u ON r.id_user = u.id
     WHERE r.id_riwayat = ?',
    [$riwayatId]
);

if (
    !$riwayat
    || (
        (int) $riwayat['id_user'] !== (int) $_SESSION['user_id']
        && ($_SESSION['user_role'] ?? '') !== 'admin'
    )
) {
    header('Location: riwayat.php');
    exit;
}

$penyakit = fetchOne(
    'SELECT * FROM penyakit WHERE nama_penyakit = ?',
    [$riwayat['penyakit_tertinggi']]
) ?: [];
$detailGejala = json_decode((string) ($riwayat['detail_gejala'] ?? '[]'), true);
$detailGejala = is_array($detailGejala) ? $detailGejala : [];

$tanggal = new DateTime($riwayat['tanggal']);
$cfPercentage = round((float) $riwayat['nilai_cf'] * 100, 1);
$certaintyInterpretation = getCertaintyInterpretation((float) $riwayat['nilai_cf']);
$solutionText = trim((string) ($penyakit['solusi'] ?? 'Belum ada saran penanganan.'));
$solutionLines = preg_split('/\R+/u', $solutionText) ?: [$solutionText];
$solutionIntro = [];
$solutionSteps = [];

foreach ($solutionLines as $solutionLine) {
    $solutionLine = trim($solutionLine);
    if ($solutionLine === '') {
        continue;
    }

    if (preg_match('/^\d+[\.\)]\s*(.+)$/u', $solutionLine, $matches)) {
        $solutionSteps[] = trim($matches[1]);
    } else {
        $solutionIntro[] = $solutionLine;
    }
}

$symptomRows = [];
foreach ($detailGejala as $symptomId => $cfUser) {
    $symptom = fetchOne(
        'SELECT kode_gejala, nama_gejala FROM gejala WHERE id_gejala = ?',
        [(int) $symptomId]
    ) ?: [];
    $symptomRows[] = [
        'code' => $symptom['kode_gejala'] ?? ('ID-' . (int) $symptomId),
        'name' => $symptom['nama_gejala'] ?? 'Gejala dari basis pengetahuan lama',
        'cf' => (float) $cfUser,
        'label' => getUserCertaintyLabel((float) $cfUser),
    ];
}

$pageTitle = 'Detail Hasil Diagnosis';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title><?php echo $pageTitle; ?> - AVES</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="assets/user-account.css?v=20260731-4" rel="stylesheet">
    <link href="assets/disease-education.css?v=20260729-3" rel="stylesheet">
    <link href="assets/diagnosis-result.css?v=20260729-8" rel="stylesheet">
</head>
<body class="user-account-page">

<header class="user-topbar">
    <div class="user-topbar__inner">
        <div class="user-brand-cluster">
            <a
                href="<?php echo $isCurrentResult ? 'konsultasi.php' : 'riwayat.php'; ?>"
                class="user-header-back"
                title="Kembali"
            >
                <span class="user-header-back__icon" aria-hidden="true">&larr;</span>
                <span class="user-header-back__label">Kembali</span>
            </a>
            <a href="index.html" class="user-brand" aria-label="Ke Beranda AVES">
                <img class="user-brand-logo" src="assets/branding/aves-blue.png" alt="AVES - Avian &amp; Exotic Animal Veterinary Services Jakarta">
            </a>
        </div>
        <div class="user-header-actions">
            <span class="user-greeting">Halo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" onclick="confirmLogout('logout.php', event)" class="user-logout">Keluar</a>
        </div>
    </div>
</header>

<main class="user-diagnosis-main">
    <section class="result-hero">
        <div>
            <p class="result-hero__eyebrow">HASIL ANALISIS</p>
            <h1><?php echo htmlspecialchars($riwayat['penyakit_tertinggi']); ?></h1>
            <p class="result-hero__latin"><?php echo htmlspecialchars($penyakit['nama_latin'] ?? '-'); ?></p>
        </div>

        <div class="result-hero__id">
            <p class="result-hero__label">ID Laporan</p>
            <strong>#<?php echo str_pad((string) $riwayatId, 3, '0', STR_PAD_LEFT); ?></strong>
            <div class="result-hero__date">
                <span>Tanggal diagnosis</span>
                <time datetime="<?php echo htmlspecialchars($tanggal->format(DateTimeInterface::ATOM)); ?>">
                    <?php echo $tanggal->format('d M Y'); ?> · <?php echo $tanggal->format('H:i'); ?> WIB
                </time>
            </div>
        </div>
    </section>

    <div class="result-layout">
        <section class="result-card result-detail-card">
            <div class="result-card-heading">
                <h2>
                    <span class="material-symbols-outlined">clinical_notes</span>
                    Detail Diagnosis
                </h2>
                <div class="result-card-actions">
                    <a
                        href="konsultasi.php"
                        class="result-retry-button"
                        title="Mulai diagnosis baru"
                    >
                        <span class="material-symbols-outlined" aria-hidden="true">restart_alt</span>
                        <span class="result-retry-button__label">Diagnosa Ulang</span>
                    </a>
                    <a
                        href="admin/laporan/laporan-hasil-diagnosis.php?scope=history&amp;riwayat_id=<?php echo (int) $riwayatId; ?>"
                        class="result-print-button"
                        title="Cetak laporan"
                    >
                        <span class="material-symbols-outlined" aria-hidden="true">print</span>
                        <span class="result-print-button__label">Cetak</span>
                    </a>
                </div>
            </div>

            <div class="result-tabs" data-result-tabs>
                <div class="result-tablist" role="tablist" aria-label="Detail diagnosis">
                    <button
                        class="result-tab"
                        id="result-tab-description"
                        type="button"
                        role="tab"
                        aria-selected="true"
                        aria-controls="result-panel-description"
                    >
                        <span class="material-symbols-outlined" aria-hidden="true">description</span>
                        Deskripsi
                    </button>
                    <button
                        class="result-tab"
                        id="result-tab-treatment"
                        type="button"
                        role="tab"
                        aria-selected="false"
                        aria-controls="result-panel-treatment"
                        tabindex="-1"
                    >
                        <span class="material-symbols-outlined" aria-hidden="true">format_list_numbered</span>
                        Penanganan
                    </button>
                    <button
                        class="result-tab"
                        id="result-tab-visual"
                        type="button"
                        role="tab"
                        aria-selected="false"
                        aria-controls="result-panel-visual"
                        tabindex="-1"
                    >
                        <span class="material-symbols-outlined" aria-hidden="true">image</span>
                        Visual &amp; Obat
                    </button>
                </div>

                <section
                    class="result-panel result-description"
                    id="result-panel-description"
                    role="tabpanel"
                    aria-labelledby="result-tab-description"
                >
                    <p class="result-description__lead">
                        <?php echo htmlspecialchars($penyakit['deskripsi'] ?? 'Tidak ada deskripsi'); ?>
                    </p>
                    <div class="result-screening-note">
                        <span class="material-symbols-outlined" aria-hidden="true">info</span>
                        <span>
                            Hasil ini merupakan screening awal berdasarkan gejala dan perhitungan
                            Certainty Factor. Pemeriksaan dokter hewan tetap diperlukan untuk
                            memastikan kondisi burung.
                        </span>
                    </div>
                </section>

                <section
                    class="result-panel"
                    id="result-panel-treatment"
                    role="tabpanel"
                    aria-labelledby="result-tab-treatment"
                    hidden
                >
                    <?php if ($solutionIntro !== []): ?>
                        <p class="result-solution-intro"><?php echo htmlspecialchars(implode(' ', $solutionIntro)); ?></p>
                    <?php endif; ?>

                    <?php if ($solutionSteps !== []): ?>
                        <ol class="result-solution-steps">
                            <?php foreach ($solutionSteps as $solutionStep): ?>
                                <li><?php echo htmlspecialchars($solutionStep); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p class="result-solution-fallback"><?php echo nl2br(htmlspecialchars($solutionText)); ?></p>
                    <?php endif; ?>
                </section>

                <section
                    class="result-panel result-panel--visual"
                    id="result-panel-visual"
                    role="tabpanel"
                    aria-labelledby="result-tab-visual"
                    hidden
                >
                    <?php
                    renderDiseaseEducation(
                        $penyakit['kode_penyakit'] ?? '',
                        $penyakit['nama_penyakit'] ?? ''
                    );
                    ?>
                </section>
            </div>
        </section>

        <aside class="result-card result-symptoms-card">
            <h2>
                <span class="material-symbols-outlined">fact_check</span>
                Gejala Teridentifikasi
            </h2>

            <div class="result-confidence" aria-label="Tingkat kepastian diagnosis">
                <div class="result-confidence__top">
                    <span>Certainty Factor</span>
                    <strong><?php echo $cfPercentage; ?>%</strong>
                </div>
                <div class="result-confidence__bar" aria-hidden="true">
                    <span style="width: <?php echo $cfPercentage; ?>%"></span>
                </div>
                <span class="result-confidence__label">
                    <?php echo htmlspecialchars($certaintyInterpretation); ?>
                </span>
            </div>

            <div class="result-symptom-scroll">
                <table class="result-symptom-table">
                    <thead>
                        <tr>
                            <th>KODE</th>
                            <th>NAMA GEJALA</th>
                            <th>TINGKAT YAKIN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($symptomRows === []): ?>
                            <tr><td colspan="3">Data gejala tidak tersedia.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($symptomRows as $symptomRow): ?>
                            <tr>
                                <td><span class="result-symptom-code"><?php echo htmlspecialchars($symptomRow['code']); ?></span></td>
                                <td><?php echo htmlspecialchars($symptomRow['name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($symptomRow['label']); ?>
                                    (<?php echo number_format($symptomRow['cf'], 1, ',', ''); ?>)
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </aside>
    </div>

</main>

<nav class="user-bottom-nav" aria-label="Navigasi utama">
    <a href="konsultasi.php"<?php echo $isCurrentResult ? ' class="is-active"' : ''; ?>>
        <span class="material-symbols-outlined">health_and_safety</span>
        <span>Diagnosis</span>
    </a>
    <a href="riwayat.php"<?php echo !$isCurrentResult ? ' class="is-active"' : ''; ?>>
        <span class="material-symbols-outlined">history</span>
        <span>History</span>
    </a>
    <a href="profile.php">
        <span class="material-symbols-outlined">person</span>
        <span>Profile</span>
    </a>
</nav>

<script>
document.querySelectorAll('[data-result-tabs]').forEach(function(tabGroup) {
    const tabs = Array.from(tabGroup.querySelectorAll('[role="tab"]'));
    const panels = Array.from(tabGroup.querySelectorAll('[role="tabpanel"]'));

    function activateTab(nextTab) {
        tabs.forEach(function(tab) {
            const selected = tab === nextTab;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
        });

        panels.forEach(function(panel) {
            panel.hidden = panel.id !== nextTab.getAttribute('aria-controls');
        });
    }

    tabs.forEach(function(tab, index) {
        tab.addEventListener('click', function() {
            activateTab(tab);
        });

        tab.addEventListener('keydown', function(event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            event.preventDefault();
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const nextIndex = (index + direction + tabs.length) % tabs.length;
            tabs[nextIndex].focus();
            activateTab(tabs[nextIndex]);
        });
    });
});
</script>

<?php $logout_url = 'logout.php'; include __DIR__ . '/includes/logout-confirmation.php'; ?>
</body>
</html>
