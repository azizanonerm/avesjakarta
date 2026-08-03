<?php
require_once __DIR__ . '/../config/security.php';
requireAdminUser('../login.php');

require_once '../config/database.php';

$counts = [
    'penyakit' => (int) fetchOne('SELECT COUNT(*) AS total FROM penyakit')['total'],
    'gejala' => (int) fetchOne('SELECT COUNT(*) AS total FROM gejala')['total'],
    'aturan' => (int) fetchOne('SELECT COUNT(*) AS total FROM aturan')['total'],
    'diagnosis' => (int) fetchOne('SELECT COUNT(*) AS total FROM riwayat')['total'],
];

$reports = [
    [
        'title' => 'Laporan Data Penyakit',
        'description' => 'Daftar penyakit kulit burung, deskripsi, dan saran penanganan awal.',
        'icon' => 'medical_information',
        'count' => $counts['penyakit'],
        'href' => 'laporan/laporan-penyakit.php',
        'color' => '#075fbd',
    ],
    [
        'title' => 'Laporan Data Gejala',
        'description' => 'Daftar seluruh gejala yang digunakan pada proses konsultasi.',
        'icon' => 'fact_check',
        'count' => $counts['gejala'],
        'href' => 'laporan/laporan-gejala.php',
        'color' => '#087f5b',
    ],
    [
        'title' => 'Laporan Basis Pengetahuan / Rule Base',
        'description' => 'Hubungan penyakit, gejala, dan nilai Certainty Factor pakar.',
        'icon' => 'rule',
        'count' => $counts['aturan'],
        'href' => 'laporan/laporan-rule-base.php',
        'color' => '#7e22ce',
    ],
    [
        'title' => 'Laporan Hasil Diagnosis',
        'description' => 'Dokumentasi riwayat konsultasi dan hasil akhir perhitungan CF.',
        'icon' => 'clinical_notes',
        'count' => $counts['diagnosis'],
        'href' => 'laporan/laporan-hasil-diagnosis.php',
        'color' => '#b45309',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title>Menu Laporan - AVES</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f7fc; color: #161c27; font-family: Inter, Arial, sans-serif; }
        header { background: #0f2d5a; color: white; padding: 20px 28px; }
        header > div { max-width: 1200px; margin: auto; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        header h1 { margin: 0; font-family: "Plus Jakarta Sans", sans-serif; font-size: 25px; }
        header a { color: white; text-decoration: none; border: 1px solid rgba(255,255,255,.5); padding: 9px 14px; border-radius: 8px; }
        main { max-width: 1200px; margin: auto; padding: 34px 22px 80px; }
        .intro { background: white; border: 1px solid #cbd5e1; border-radius: 14px; padding: 24px; margin-bottom: 24px; }
        .intro h2 { margin: 0 0 8px; font-family: "Plus Jakarta Sans", sans-serif; }
        .intro p { margin: 0; color: #475569; line-height: 1.7; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .card { display: flex; flex-direction: column; min-height: 230px; padding: 24px; background: white; border: 1px solid #cbd5e1; border-radius: 14px; text-decoration: none; color: inherit; box-shadow: 0 2px 8px rgba(15,45,90,.06); transition: .2s ease; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(15,45,90,.12); border-color: var(--accent); }
        .icon { width: 52px; height: 52px; display: grid; place-items: center; border-radius: 12px; color: white; background: var(--accent); }
        .icon span { font-size: 29px; }
        .card h3 { margin: 18px 0 8px; font-family: "Plus Jakarta Sans", sans-serif; font-size: 19px; }
        .card p { margin: 0; color: #64748b; line-height: 1.6; }
        .card footer { margin-top: auto; padding-top: 20px; display: flex; justify-content: space-between; color: var(--accent); font-weight: 700; }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } header > div { align-items: center; } }
    </style>
    <link href="assets/admin-theme.css?v=20260731-5" rel="stylesheet">
</head>
<body class="admin-shell admin-sidebar-shell reports-page">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <header>
        <div>
            <div>
                <h1>Menu Laporan</h1>
            </div>
            <div class="admin-header-actions">
                <a
                    href="../logout.php"
                    class="admin-header-logout"
                    onclick="confirmLogout('../logout.php', event)"
                >Keluar</a>
            </div>
        </div>
    </header>
    <main>
        <section class="intro">
            <h2>Empat Laporan Sistem Pakar</h2>
            <p>Pada sistem ini terdapat empat menu laporan, yaitu laporan data penyakit, laporan data gejala, laporan basis pengetahuan, dan laporan hasil diagnosis. Keempat laporan tersebut digunakan untuk membantu administrator dalam melihat dan mencetak data yang berkaitan dengan proses diagnosis penyakit kulit burung menggunakan metode Certainty Factor.</p>
        </section>

        <section class="grid">
            <?php foreach ($reports as $report): ?>
                <a class="card" style="--accent: <?php echo $report['color']; ?>" href="<?php echo htmlspecialchars($report['href']); ?>">
                    <div class="icon"><span class="material-symbols-outlined"><?php echo htmlspecialchars($report['icon']); ?></span></div>
                    <h3><?php echo htmlspecialchars($report['title']); ?></h3>
                    <p><?php echo htmlspecialchars($report['description']); ?></p>
                    <footer>
                        <span><?php echo $report['count']; ?> data</span>
                        <span>Buka Laporan &rarr;</span>
                    </footer>
                </a>
            <?php endforeach; ?>
        </section>
    </main>
    <?php
    $logout_url = '../logout.php';
    require __DIR__ . '/../includes/logout-confirmation.php';
    ?>
</body>
</html>
