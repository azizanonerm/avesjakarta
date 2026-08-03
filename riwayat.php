<?php
/**
 * Riwayat Diagnosa - User History
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/config/security.php';

// Cek login
requireRegularUser('login.php', 'admin/dashboard.php');

require_once 'config/database.php';
require_once 'config/certainty.php';

$user_id = $_SESSION['user_id'];
$search = limitedText($_GET['search'] ?? '', 100);
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get riwayat diagnosa user
if ($search) {
    $riwayat_list = fetchAll(
        'SELECT * FROM riwayat WHERE id_user = ? AND penyakit_tertinggi LIKE ? ORDER BY tanggal DESC LIMIT ? OFFSET ?',
        [$user_id, '%' . $search . '%', $limit, $offset]
    );
    $total = fetchOne('SELECT COUNT(*) as count FROM riwayat WHERE id_user = ? AND penyakit_tertinggi LIKE ?', [$user_id, '%' . $search . '%'])['count'];
} else {
    $riwayat_list = fetchAll(
        'SELECT * FROM riwayat WHERE id_user = ? ORDER BY tanggal DESC LIMIT ? OFFSET ?',
        [$user_id, $limit, $offset]
    );
    $total = fetchOne('SELECT COUNT(*) as count FROM riwayat WHERE id_user = ?', [$user_id])['count'];
}

$total_pages = ceil($total / $limit);

$page_title = 'Riwayat Saya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title><?php echo $page_title; ?> - AVES</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="assets/user-account.css?v=20260731-4" rel="stylesheet">
</head>
<body class="user-account-page user-account-page--header-back">

<!-- Header -->
<header class="user-topbar">
    <div class="user-topbar__inner">
        <div class="user-brand-cluster">
            <a href="konsultasi.php" class="user-header-back" title="Kembali ke konsultasi">
                <span class="user-header-back__icon" aria-hidden="true">&larr;</span>
                <span class="user-header-back__label">Kembali</span>
            </a>
            <a href="index.html" class="user-brand" aria-label="Ke Beranda AVES">
                <img class="user-brand-logo" src="assets/branding/aves-blue.png" alt="AVES - Avian &amp; Exotic Animal Veterinary Services Jakarta">
            </a>
        </div>
        <div class="user-header-actions">
            <span class="user-greeting">Halo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" onclick="confirmLogout('logout.php', event)" class="user-logout">
                Keluar
            </a>
        </div>
    </div>
</header>

<main class="user-account-main">

    <section class="user-hero">
        <div class="user-hero__content">
            <div>
                <span class="user-hero__eyebrow">
                    <span class="material-symbols-outlined">clinical_notes</span>
                    Arsip Konsultasi
                </span>
                <h1>Riwayat Diagnosis</h1>
                <p>Tinjau kembali hasil diagnosis yang pernah dilakukan dan pantau nilai Certainty Factor setiap konsultasi.</p>
            </div>
            <div class="user-hero__count">
                <strong><?php echo (int) $total; ?></strong>
                <span>Total riwayat<?php echo $search ? ' ditemukan' : ''; ?></span>
            </div>
        </div>
    </section>

    <div class="user-toolbar">
        <form method="GET" class="user-search">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama hasil diagnosis..." class="user-search__field" aria-label="Cari riwayat diagnosis">
            <button type="submit" class="user-button">
                <span class="material-symbols-outlined">search</span>
                Cari
            </button>
        </form>
        <div class="user-toolbar__actions flex gap-2">
            <a href="konsultasi.php" class="user-button">
                <span class="material-symbols-outlined">add_circle</span>
                Diagnosa Baru
            </a>
        </div>
    </div>

    <style media="print">
        @media print {
            .history-card { break-inside: avoid; box-shadow: none; }
        }
    </style>

    <!-- Riwayat List -->
    <div class="history-list">
        <?php if (!empty($riwayat_list)): ?>
            <?php foreach ($riwayat_list as $riwayat): 
                $cf_percentage = round($riwayat['nilai_cf'] * 100, 1);
                $tanggal = new DateTime($riwayat['tanggal']);
                $cf_level = getCertaintyInterpretation($riwayat['nilai_cf']);
            ?>
                <article class="history-card">
                    <div class="history-card__top">
                        <div class="history-card__meta">
                            <span>
                                <span class="material-symbols-outlined text-sm">tag</span>
                                ID #<?php echo str_pad($riwayat['id_riwayat'], 3, '0', STR_PAD_LEFT); ?>
                            </span>
                            <span>
                                <span class="material-symbols-outlined text-sm">calendar_today</span>
                                <?php echo $tanggal->format('d M Y'); ?>
                            </span>
                        </div>
                        <span class="user-badge">
                            <?php echo $cf_level; ?>
                        </span>
                    </div>
                    
                    <h2 class="history-card__title">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">health_and_safety</span>
                        <?php echo htmlspecialchars($riwayat['penyakit_tertinggi']); ?>
                    </h2>
                    
                    <div class="history-card__meter">
                        <span class="history-card__meter-label">Certainty Factor</span>
                        <strong class="history-card__percent"><?php echo $cf_percentage; ?>%</strong>
                        <div class="history-card__track">
                            <div class="history-card__fill" style="width: <?php echo $cf_percentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="history-card__footer">
                        <a href="detail-riwayat.php?id=<?php echo $riwayat['id_riwayat']; ?>" class="history-detail-link">
                            Lihat Detail Diagnosis
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="user-empty-state">
                <span class="material-symbols-outlined">history</span>
                <p><?php echo $search ? 'Riwayat yang dicari tidak ditemukan.' : 'Belum ada riwayat diagnosis.'; ?></p>
                <a href="konsultasi.php" class="history-detail-link">Mulai diagnosis sekarang</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav class="user-pagination" aria-label="Navigasi halaman riwayat">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" aria-label="Halaman sebelumnya">←</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $i === $page ? 'is-active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" aria-label="Halaman berikutnya">→</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

</main>

<!-- Bottom Navigation -->
<nav class="user-bottom-nav" aria-label="Navigasi utama pengguna">
    <a href="konsultasi.php">
        <span class="material-symbols-outlined">health_and_safety</span>
        <span>Diagnosis</span>
    </a>
    <a href="riwayat.php" class="is-active" aria-current="page">
        <span class="material-symbols-outlined">history</span>
        <span>History</span>
    </a>
    <a href="profile.php">
        <span class="material-symbols-outlined">person</span>
        <span>Profile</span>
    </a>
</nav>

<?php $logout_url = 'logout.php'; include __DIR__ . '/includes/logout-confirmation.php'; ?>
</body>
</html>
