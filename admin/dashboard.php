<?php
/**
 * Admin Dashboard
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/../config/security.php';

// Cek login dan role admin
requireAdminUser('../login.php');

if (!empty($_SESSION['must_change_password'])) {
    $_SESSION['admin_password_warning'] = true;
    unset($_SESSION['must_change_password']);
}

require_once '../config/database.php';

// Ambil statistik
$total_penyakit = fetchOne('SELECT COUNT(*) as count FROM penyakit')['count'];
$total_gejala = fetchOne('SELECT COUNT(*) as count FROM gejala')['count'];
$total_aturan = fetchOne('SELECT COUNT(*) as count FROM aturan')['count'];
$total_konsultasi = fetchOne('SELECT COUNT(*) as count FROM riwayat')['count'];

// Ambil riwayat konsultasi terbaru
$recent_consultations = fetchAll('
    SELECT r.*, u.nama 
    FROM riwayat r 
    JOIN users u ON r.id_user = u.id 
    ORDER BY r.tanggal DESC 
    LIMIT 10
');

// Hitung akurasi rata-rata
$avg_cf = fetchOne('SELECT AVG(nilai_cf) as avg_cf FROM riwayat')['avg_cf'];
$avg_cf = round($avg_cf * 100, 1);

$admin_name = trim((string) ($_SESSION['user_name'] ?? 'Admin'));
$admin_name = $admin_name !== '' ? $admin_name : 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));

$page_title = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title><?php echo $page_title; ?> - AVES</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-secondary-fixed-variant": "#00458d",
                        "on-secondary-fixed": "#001b3e",
                        "primary-container": "#0f2d5a",
                        "on-tertiary-container": "#389fd1",
                        "tertiary-fixed-dim": "#7fd0ff",
                        "on-primary-fixed": "#001a40",
                        "primary": "#00183c",
                        "secondary-container": "#5498ff",
                        "outline": "#747780",
                        "primary-fixed-dim": "#adc7fd",
                        "surface-tint": "#455e8e",
                        "on-primary-fixed-variant": "#2c4675",
                        "on-primary-container": "#7c96c9",
                        "on-error-container": "#93000a",
                        "primary-fixed": "#d7e2ff",
                        "inverse-on-surface": "#ecf0ff",
                        "surface-variant": "#dde2f3",
                        "surface": "#f9f9ff",
                        "surface-bright": "#f9f9ff",
                        "on-error": "#ffffff",
                        "on-surface": "#161c27",
                        "on-tertiary-fixed": "#001e2d",
                        "secondary-fixed-dim": "#aac7ff",
                        "secondary": "#005db9",
                        "surface-dim": "#d4daea",
                        "on-primary": "#ffffff",
                        "surface-container-low": "#f1f3ff",
                        "background": "#f9f9ff",
                        "surface-container-high": "#e3e8f9",
                        "on-secondary": "#ffffff",
                        "on-background": "#161c27",
                        "surface-container": "#e8eeff",
                        "on-tertiary-fixed-variant": "#004c6a",
                        "on-tertiary": "#ffffff",
                        "tertiary-fixed": "#c5e7ff",
                        "error": "#ba1a1a",
                        "secondary-fixed": "#d6e3ff",
                        "inverse-primary": "#adc7fd",
                        "tertiary": "#001c2a",
                        "tertiary-container": "#003247",
                        "error-container": "#ffdad6",
                        "outline-variant": "#c4c6d0",
                        "inverse-surface": "#2a303d",
                        "surface-container-highest": "#dde2f3",
                        "on-secondary-container": "#003065",
                        "surface-container-lowest": "#ffffff",
                        "on-surface-variant": "#44474f"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "margin-mobile": "16px",
                        "container-max": "1320px",
                        "base": "8px",
                        "margin-desktop": "24px",
                        "gutter": "16px"
                    },
                    "fontFamily": {
                        "headline-h2": ["Plus Jakarta Sans"],
                        "body-md": ["Inter"],
                        "headline-h1": ["Plus Jakarta Sans"],
                        "headline-h1-mobile": ["Plus Jakarta Sans"],
                        "display-lg": ["Plus Jakarta Sans"],
                        "body-lg": ["Inter"],
                        "label-caps": ["Inter"],
                        "button": ["Inter"]
                    },
                    "fontSize": {
                        "headline-h2": ["24px", { "lineHeight": "1.4", "fontWeight": "600" }],
                        "body-md": ["16px", { "lineHeight": "1.5", "fontWeight": "400" }],
                        "headline-h1": ["32px", { "lineHeight": "1.3", "fontWeight": "700" }],
                        "headline-h1-mobile": ["24px", { "lineHeight": "1.3", "fontWeight": "700" }],
                        "display-lg": ["48px", { "lineHeight": "1.2", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "body-lg": ["18px", { "lineHeight": "1.6", "fontWeight": "400" }],
                        "label-caps": ["12px", { "lineHeight": "1.2", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "button": ["15px", { "lineHeight": "1", "fontWeight": "600" }]
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            min-height: max(884px, 100dvh);
        }
    </style>
    <link href="assets/admin-theme.css?v=20260731-5" rel="stylesheet">
</head>
<body class="admin-shell admin-sidebar-shell admin-dashboard-shell bg-background text-on-background font-body-md text-body-md flex h-screen overflow-hidden">

<?php require __DIR__ . '/includes/sidebar.php'; ?>

<!-- Main Content -->
<main class="admin-dashboard-main flex-1 md:ml-64 flex flex-col h-full bg-background overflow-y-auto">
    
    <!-- Top Bar -->
    <header class="bg-surface sticky top-0 z-10 border-b border-outline-variant px-margin-desktop py-4 flex justify-between items-center h-[70px] shadow-sm">
        <div class="flex items-center gap-4">
            <h1 class="font-headline-h2 text-headline-h2 text-on-surface">Dashboard</h1>
        </div>
        <div class="admin-topbar-actions">
            <span class="text-on-surface-variant font-body-md text-body-md hidden sm:block" id="current-date"></span>
            <a
                href="../logout.php"
                class="admin-topbar-logout"
                onclick="confirmLogout('../logout.php', event)"
            >
                <span class="material-symbols-outlined admin-topbar-logout__icon" aria-hidden="true">logout</span>
                <span class="admin-topbar-logout__label">Keluar</span>
            </a>
        </div>
    </header>
    
    <div class="p-margin-desktop space-y-8 max-w-container-max mx-auto w-full">
        <?php if (!empty($_SESSION['admin_password_warning'])): ?>
            <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900">
                <strong>Keamanan akun:</strong>
                password administrator masih menggunakan password lama yang mudah ditebak.
                Dashboard tetap dapat digunakan, tetapi sebaiknya segera ubah melalui
                <a href="../profile.php" class="font-bold underline">Profil Akun</a>.
            </section>
        <?php endif; ?>
        
        <!-- Metrics Cards -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
            <!-- Total Penyakit -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-[0_1px_3px_rgba(0,0,0,0.06)] hover:shadow-md hover:border-primary transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-secondary-container/20 text-secondary rounded-lg">
                        <span class="material-symbols-outlined" data-icon="medical_services">medical_services</span>
                    </div>
                </div>
                <h3 class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider mb-1">Total Penyakit</h3>
                <p class="font-display-lg text-display-lg text-on-surface"><?php echo $total_penyakit; ?></p>
            </div>
            
            <!-- Total Gejala -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-[0_1px_3px_rgba(0,0,0,0.06)] hover:shadow-md hover:border-primary transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-tertiary-container/20 text-tertiary rounded-lg">
                        <span class="material-symbols-outlined" data-icon="checklist">checklist</span>
                    </div>
                </div>
                <h3 class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider mb-1">Total Gejala</h3>
                <p class="font-display-lg text-display-lg text-on-surface"><?php echo $total_gejala; ?></p>
            </div>
            
            <!-- Total Rule -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-[0_1px_3px_rgba(0,0,0,0.06)] hover:shadow-md hover:border-primary transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-primary-container/20 text-primary rounded-lg">
                        <span class="material-symbols-outlined" data-icon="rule">rule</span>
                    </div>
                </div>
                <h3 class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider mb-1">Total Rule</h3>
                <p class="font-display-lg text-display-lg text-on-surface"><?php echo $total_aturan; ?></p>
            </div>
            
            <!-- Akurasi Rata-rata -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-[0_1px_3px_rgba(0,0,0,0.06)] hover:shadow-md hover:border-primary transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-surface-container-high rounded-full opacity-50"></div>
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <div class="p-3 bg-surface-tint/20 text-surface-tint rounded-lg">
                        <span class="material-symbols-outlined" data-icon="fact_check">fact_check</span>
                    </div>
                </div>
                <h3 class="font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider mb-1 relative z-10">Akurasi Rata-rata</h3>
                <p class="font-display-lg text-display-lg text-primary relative z-10"><?php echo $avg_cf; ?>%</p>
            </div>
        </section>
        
        <!-- Recent Consultations -->
        <section class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.06)]">
            <div class="px-6 py-5 border-b border-outline-variant">
                <h2 class="font-headline-h2 text-headline-h2 text-on-surface">Log Konsultasi Terbaru</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant border-b border-outline-variant">
                            <th class="px-6 py-3 font-label-caps text-label-caps uppercase tracking-wider">Waktu</th>
                            <th class="px-6 py-3 font-label-caps text-label-caps uppercase tracking-wider">Pengguna</th>
                            <th class="px-6 py-3 font-label-caps text-label-caps uppercase tracking-wider">Hasil Diagnosis</th>
                            <th class="px-6 py-3 font-label-caps text-label-caps uppercase tracking-wider">CF Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant text-body-md font-body-md">
                        <?php foreach ($recent_consultations as $consultation): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="px-6 py-4 text-on-surface-variant"><?php echo date('d M Y H:i', strtotime($consultation['tanggal'])); ?></td>
                                <td class="px-6 py-4 font-semibold text-on-surface"><?php echo htmlspecialchars($consultation['nama']); ?></td>
                                <td class="px-6 py-4 font-semibold text-primary"><?php echo htmlspecialchars($consultation['penyakit_tertinggi']); ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-secondary"><?php echo round($consultation['nilai_cf'] * 100, 1); ?>%</span>
                                        <div class="w-16 h-2 bg-surface-container rounded-full overflow-hidden">
                                            <div class="h-full bg-secondary" style="width: <?php echo round($consultation['nilai_cf'] * 100, 1); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
    </div>
</main>

<script>
    // Set current date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('current-date').textContent = new Date().toLocaleDateString('id-ID', options);

    // Dashboard selalu dibuka dari bagian paling atas agar kartu menu tidak
    // tersembunyi di belakang header akibat posisi scroll yang dipulihkan browser.
    window.addEventListener('pageshow', function () {
        document.querySelector('.admin-dashboard-main')?.scrollTo(0, 0);
    });
</script>

<?php
$logout_url = '../logout.php';
require __DIR__ . '/../includes/logout-confirmation.php';
?>

</body>
</html>
