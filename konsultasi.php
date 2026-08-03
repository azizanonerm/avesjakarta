<?php
/**
 * Konsultasi Page
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/config/security.php';

// Cek apakah user sudah login
requireRegularUser('login.php', 'admin/dashboard.php');

require_once 'config/database.php';
require_once 'config/certainty.php';

$certainty_options = getUserCertaintyOptions();
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// Ambil dan kelompokkan 60 gejala Bab 3 agar pilihan tetap mudah dipindai.
$gejala_list = fetchAll("SELECT id_gejala, kode_gejala, nama_gejala, kategori, deskripsi FROM gejala ORDER BY kategori, kode_gejala");

$grouped_gejala = [];
foreach ($gejala_list as $gejala) {
    $category = $gejala['kategori'] ?: 'Gejala Umum';
    $grouped_gejala[$category][] = $gejala;
}

$page_title = 'Konsultasi Diagnostik';
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
        body { background-color: #F7F9FC; }
        .card-shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .card-hover:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-color: #00183c;
            transform: translateY(-2px);
            transition: all 0.15s ease-in-out;
        }
    </style>
</head>
<body class="font-body-md text-body-md text-on-surface flex flex-col min-h-screen overflow-x-hidden pb-20">

<!-- TopAppBar -->
<header class="user-topbar">
    <div class="user-topbar__inner">
        <div class="user-brand-cluster" data-aves-back-host data-aves-back-theme="dark">
            <a href="index.html" class="user-brand" aria-label="Ke Beranda AVES">
                <img src="assets/branding/aves-blue.png" alt="AVES - Avian &amp; Exotic Animal Veterinary Services Jakarta" class="user-brand-logo">
            </a>
        </div>
        <div class="user-header-actions">
            <span class="user-greeting">Halo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" onclick="confirmLogout('logout.php', event)" class="user-logout">Keluar</a>
        </div>
    </div>
</header>

<!-- Main Content -->
<main class="flex-grow px-margin-mobile md:px-margin-desktop py-8 max-w-container-max mx-auto w-full flex flex-col gap-8">
    
    <!-- Header Halaman -->
    <section>
        <h1 class="font-headline-h1 text-headline-h1-mobile md:text-headline-h1 text-primary">Konsultasi Diagnostik</h1>
    </section>

    <!-- Konsultasi Form -->
    <form id="consultationForm" method="POST" action="proses_diagnosa.php" class="flex flex-col lg:flex-row gap-gutter relative items-start">
        <?php echo csrfField(); ?>
        
        <!-- Left: Symptom Groups -->
        <section class="w-full lg:w-2/3 flex flex-col gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl card-shadow card-hover overflow-hidden">
                <div class="px-6 py-4 bg-surface-container-lowest">
                    <h3 class="font-headline-h2 text-headline-h2 text-primary">Pilih Gejala yang Dialami Burung</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-2">Centang gejala yang Anda amati, lalu atur tingkat keyakinan Anda</p>
                </div>
                
                <div class="px-6 pb-4 flex flex-col gap-6 border-t border-surface-variant">
                    <?php foreach ($grouped_gejala as $category => $category_symptoms): ?>
                        <section class="flex flex-col gap-3 pt-5" aria-labelledby="category-<?php echo md5($category); ?>">
                            <div class="flex items-center gap-2 border-b border-surface-variant pb-2">
                                <span class="material-symbols-outlined text-secondary">fact_check</span>
                                <h4 id="category-<?php echo md5($category); ?>" class="font-semibold text-primary"><?php echo htmlspecialchars($category); ?></h4>
                                <span class="ml-auto text-xs text-outline"><?php echo count($category_symptoms); ?> gejala</span>
                            </div>
                            <?php foreach ($category_symptoms as $gejala): ?>
                        <div class="flex flex-col gap-2 p-4 rounded-lg border border-surface-variant bg-surface-bright">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="gejala[<?php echo $gejala['id_gejala']; ?>]" 
                                    value="1"
                                    class="mt-1 w-5 h-5 rounded border-outline text-secondary focus:ring-secondary focus:ring-2"
                                    onchange="toggleCFInput(this)"
                                />
                                <div class="flex flex-col">
                                    <span class="font-body-md text-body-md text-on-surface font-semibold"><?php echo htmlspecialchars($gejala['kode_gejala'] . ' - ' . $gejala['nama_gejala']); ?></span>
                                    <span class="font-body-md text-sm text-on-surface-variant"><?php echo htmlspecialchars($gejala['deskripsi']); ?></span>
                                </div>
                            </label>
                            
                            <!-- CF pengguna: pilihan diskrit sesuai tabel skripsi -->
                            <div class="pl-8 pt-2 flex flex-col gap-2 w-full cf-input hidden">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1 text-sm">
                                    <label for="cf-<?php echo $gejala['id_gejala']; ?>" class="font-semibold text-on-surface-variant">Tingkat Keyakinan Anda</label>
                                    <span class="text-secondary font-bold cf-value">Belum dipilih</span>
                                </div>
                                <select
                                    id="cf-<?php echo $gejala['id_gejala']; ?>"
                                    name="cf[<?php echo $gejala['id_gejala']; ?>]"
                                    class="cf-select w-full rounded-lg border-outline-variant bg-white text-on-surface focus:border-secondary focus:ring-secondary"
                                    onchange="updateCFValue(this)"
                                    disabled
                                >
                                    <option value="" selected disabled>-- Pilih tingkat keyakinan --</option>
                                    <?php foreach ($certainty_options as $option): ?>
                                        <option value="<?php echo $option['value']; ?>" data-label="<?php echo htmlspecialchars($option['label']); ?>">
                                            <?php echo htmlspecialchars($option['label']); ?> (<?php echo str_replace('.', ',', $option['value']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Right: Sticky Summary -->
        <aside class="w-full lg:w-1/3 lg:sticky lg:top-20 lg:max-h-[calc(100vh-10rem)] self-start flex flex-col gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 card-shadow flex flex-col lg:max-h-[calc(100vh-10rem)]">
                <h3 class="shrink-0 font-headline-h2 text-headline-h2 text-primary mb-4 border-b border-surface-variant pb-2">Ringkasan Pilihan</h3>
                <div class="flex flex-col gap-3 mb-6 min-h-[150px] max-h-72 overflow-y-auto overscroll-contain pr-1 lg:min-h-0 lg:max-h-none lg:flex-1" id="summary-list">
                    <p class="text-on-surface-variant text-sm italic">Belum ada gejala yang dipilih</p>
                </div>
                <div class="shrink-0 pt-4 border-t border-surface-variant flex justify-between items-center mb-6">
                    <span class="font-body-md text-on-surface-variant">Total Gejala:</span>
                    <span class="font-headline-h2 text-primary font-bold" id="total-symptoms">0</span>
                </div>
                <button 
                    type="submit"
                    class="shrink-0 min-h-12 w-full whitespace-nowrap bg-secondary text-on-secondary font-button text-button px-4 py-3 rounded-lg hover:bg-[#004bb5] transition-colors shadow-sm flex justify-center items-center gap-2"
                >
                    Proses Diagnosa
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </button>
            </div>
        </aside>
    </form>
    
</main>

<!-- Footer -->
<footer class="bg-primary dark:bg-primary w-full bottom-0 flat no shadows flex flex-col md:flex-row justify-between items-center px-margin-desktop py-8 max-w-container-max mx-auto text-on-primary mt-auto">
    <span><img src="assets/branding/aves-blue.png" alt="AVES - Avian &amp; Exotic Animal Veterinary Services Jakarta" class="aves-logo-on-dark h-20 w-auto object-contain"></span>
    <span class="font-body-md text-body-md opacity-70 mt-4 md:mt-0">© 2026 AVES — Avian &amp; Exotic Animal Veterinary Services Jakarta</span>
</footer>

<script>
    function toggleCFInput(checkbox) {
        const cfInput = checkbox.closest('.flex.flex-col.gap-2').querySelector('.cf-input');
        const cfSelect = cfInput.querySelector('.cf-select');
        const cfValue = cfInput.querySelector('.cf-value');

        if (checkbox.checked) {
            cfInput.classList.remove('hidden');
            cfSelect.disabled = false;
            cfSelect.required = true;
        } else {
            cfInput.classList.add('hidden');
            cfSelect.disabled = true;
            cfSelect.required = false;
            cfSelect.value = '';
            cfValue.textContent = 'Belum dipilih';
        }
        updateSummary();
    }
    
    function updateCFValue(select) {
        const cfValue = select.closest('.cf-input').querySelector('.cf-value');
        const selectedOption = select.options[select.selectedIndex];
        const label = selectedOption.dataset.label || 'Belum dipilih';

        const displayValue = Number(select.value).toFixed(1).replace('.', ',');
        cfValue.textContent = select.value === '' ? label : `${label} (${displayValue})`;
        updateSummary();
    }
    
    function updateSummary() {
        const summaryList = document.getElementById('summary-list');
        const totalSymptoms = document.getElementById('total-symptoms');
        const checkboxes = document.querySelectorAll('input[name^="gejala"]');
        
        let selectedItems = [];
        let count = 0;
        
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const label = checkbox.closest('.flex.flex-col.gap-2').querySelector('.font-semibold');
                const cfSelect = checkbox.closest('.flex.flex-col.gap-2').querySelector('select[name^="cf"]');
                const selectedOption = cfSelect.options[cfSelect.selectedIndex];
                const certaintyLabel = cfSelect.value === ''
                    ? 'Pilih keyakinan'
                    : `${selectedOption.dataset.label} (${Number(cfSelect.value).toFixed(1).replace('.', ',')})`;
                
                selectedItems.push(`
                    <div class="flex justify-between items-center bg-surface p-3 rounded-lg border border-surface-variant">
                        <span class="font-body-md text-sm text-on-surface font-semibold truncate max-w-[180px]">${label.textContent}</span>
                        <span class="bg-secondary-container text-on-secondary-container font-label-caps text-[10px] px-2 py-1 rounded-full text-right">${certaintyLabel}</span>
                    </div>
                `);
                count++;
            }
        });
        
        if (selectedItems.length === 0) {
            summaryList.innerHTML = '<p class="text-on-surface-variant text-sm italic">Belum ada gejala yang dipilih</p>';
        } else {
            summaryList.innerHTML = selectedItems.join('');
        }
        
        totalSymptoms.textContent = count;
    }

    document.getElementById('consultationForm')?.addEventListener('submit', function (event) {
        const selectedSymptoms = document.querySelectorAll('input[name^="gejala"]:checked');
        if (selectedSymptoms.length === 0) {
            event.preventDefault();
            window.showNotificationModal('Pilih minimal satu gejala sebelum memproses diagnosis.', 'Data Belum Lengkap');
        }
    });
</script>

<!-- Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex justify-around z-50">
    <a href="konsultasi.php" class="flex-1 flex flex-col items-center justify-center py-3 text-secondary bg-blue-50 border-t-2 border-secondary">
        <span class="material-symbols-outlined">health_and_safety</span>
        <span class="text-xs mt-1">Diagnosis</span>
    </a>
    <a href="riwayat.php" class="flex-1 flex flex-col items-center justify-center py-3 text-gray-600 hover:text-secondary hover:bg-gray-50">
        <span class="material-symbols-outlined">history</span>
        <span class="text-xs mt-1">History</span>
    </a>
    <a href="profile.php" class="flex-1 flex flex-col items-center justify-center py-3 text-gray-600 hover:text-secondary hover:bg-gray-50">
        <span class="material-symbols-outlined">person</span>
        <span class="text-xs mt-1">Profile</span>
    </a>
</nav>

<div class="pb-20"></div>

<?php $logout_url = 'logout.php'; include __DIR__ . '/includes/logout-confirmation.php'; ?>
<?php $notification_title = 'Data Belum Lengkap'; $notification_message = $error; include __DIR__ . '/includes/notification-modal.php'; ?>
<?php $back_url = 'index.html'; $back_label = 'Beranda'; include __DIR__ . '/includes/back-button.php'; ?>
</body>
</html>
