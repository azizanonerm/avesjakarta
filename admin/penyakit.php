<?php
/**
 * Admin - Manajemen Penyakit Kulit
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/../config/security.php';

// Cek login dan role admin
requireAdminUser('../login.php');

require_once '../config/database.php';

$error = '';
$success = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();
    $action = limitedText($_POST['action'] ?? '', 20);
    
    if ($action === 'add') {
        $kode_penyakit = strtoupper(limitedText($_POST['kode_penyakit'] ?? '', 10));
        $nama_penyakit = limitedText($_POST['nama_penyakit'] ?? '', 100);
        $nama_latin = limitedText($_POST['nama_latin'] ?? '', 150);
        $deskripsi = limitedText($_POST['deskripsi'] ?? '', 5000);
        $solusi = limitedText($_POST['solusi'] ?? '', 5000);
        
        // Validasi input
        if (empty($kode_penyakit)) {
            $error = 'Kode penyakit harus diisi';
        } elseif (!preg_match('/^P\d{3}$/', $kode_penyakit)) {
            $error = 'Kode penyakit harus memakai format P001';
        } elseif (empty($nama_penyakit)) {
            $error = 'Nama penyakit harus diisi';
        } elseif (strlen($kode_penyakit) > 10) {
            $error = 'Kode penyakit maksimal 10 karakter';
        } elseif (strlen($nama_penyakit) > 100) {
            $error = 'Nama penyakit maksimal 100 karakter';
        } else {
            try {
                $result = execute(
                    'INSERT INTO penyakit (kode_penyakit, nama_penyakit, nama_latin, deskripsi, solusi) VALUES (?, ?, ?, ?, ?)',
                    [$kode_penyakit, $nama_penyakit, $nama_latin, $deskripsi, $solusi]
                );
                if ($result > 0) {
                    $success = 'Penyakit berhasil ditambahkan';
                    header("Refresh:1; url=penyakit.php");
                } else {
                    $error = 'Gagal menambahkan penyakit';
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = 'Kode penyakit sudah ada, gunakan kode yang berbeda';
                } else {
                    error_log('Disease insert failed: ' . $e->getMessage());
                    $error = 'Data penyakit belum dapat disimpan';
                }
            } catch (Exception $e) {
                error_log('Disease insert failed: ' . $e->getMessage());
                $error = 'Data penyakit belum dapat disimpan';
            }
        }
    } elseif ($action === 'edit') {
        $id_penyakit = intval($_POST['id_penyakit'] ?? 0);
        $kode_penyakit = strtoupper(limitedText($_POST['kode_penyakit'] ?? '', 10));
        $nama_penyakit = limitedText($_POST['nama_penyakit'] ?? '', 100);
        $nama_latin = limitedText($_POST['nama_latin'] ?? '', 150);
        $deskripsi = limitedText($_POST['deskripsi'] ?? '', 5000);
        $solusi = limitedText($_POST['solusi'] ?? '', 5000);
        
        // Validasi input
        if ($id_penyakit <= 0) {
            $error = 'ID penyakit tidak valid';
        } elseif (empty($kode_penyakit)) {
            $error = 'Kode penyakit harus diisi';
        } elseif (!preg_match('/^P\d{3}$/', $kode_penyakit)) {
            $error = 'Kode penyakit harus memakai format P001';
        } elseif (empty($nama_penyakit)) {
            $error = 'Nama penyakit harus diisi';
        } elseif (strlen($kode_penyakit) > 10) {
            $error = 'Kode penyakit maksimal 10 karakter';
        } elseif (strlen($nama_penyakit) > 100) {
            $error = 'Nama penyakit maksimal 100 karakter';
        } else {
            try {
                $result = execute(
                    'UPDATE penyakit SET kode_penyakit = ?, nama_penyakit = ?, nama_latin = ?, deskripsi = ?, solusi = ? WHERE id_penyakit = ?',
                    [$kode_penyakit, $nama_penyakit, $nama_latin, $deskripsi, $solusi, $id_penyakit]
                );
                if ($result > 0) {
                    $success = 'Penyakit berhasil diperbarui';
                    header("Refresh:1; url=penyakit.php");
                } else {
                    $error = 'Tidak ada perubahan atau ID penyakit tidak ditemukan';
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = 'Kode penyakit sudah ada, gunakan kode yang berbeda';
                } else {
                    error_log('Disease update failed: ' . $e->getMessage());
                    $error = 'Data penyakit belum dapat diperbarui';
                }
            } catch (Exception $e) {
                error_log('Disease update failed: ' . $e->getMessage());
                $error = 'Data penyakit belum dapat diperbarui';
            }
        }
    } elseif ($action === 'delete') {
        $id_penyakit = intval($_POST['id_penyakit'] ?? 0);
        
        if ($id_penyakit <= 0) {
            $error = 'ID penyakit tidak valid';
        } else {
            try {
                // Hapus aturan terkait terlebih dahulu (CASCADE sudah ada di DB, tapi double-check)
                execute('DELETE FROM aturan WHERE id_penyakit = ?', [$id_penyakit]);
                
                // Kemudian hapus penyakit
                $result = execute('DELETE FROM penyakit WHERE id_penyakit = ?', [$id_penyakit]);
                
                if ($result > 0) {
                    $success = 'Penyakit berhasil dihapus';
                    header("Refresh:1; url=penyakit.php");
                } else {
                    $error = 'Penyakit tidak ditemukan';
                }
            } catch (Exception $e) {
                error_log('Disease delete failed: ' . $e->getMessage());
                $error = 'Data penyakit belum dapat dihapus';
            }
        }
    }
}

// Generate kode penyakit otomatis
function generateKodePenyakit() {
    $last_penyakit = fetchOne('SELECT kode_penyakit FROM penyakit ORDER BY id_penyakit DESC LIMIT 1');
    if ($last_penyakit) {
        $last_code = $last_penyakit['kode_penyakit'];
        if (preg_match('/P(\d+)/', $last_code, $matches)) {
            $next_num = intval($matches[1]) + 1;
            return 'P' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        }
    }
    return 'P001';
}

$suggested_code = generateKodePenyakit();

// Get list penyakit
$search = limitedText($_GET['search'] ?? '', 100);
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$limit = 10;
$offset = ($page - 1) * $limit;

if ($search) {
    $penyakit_list = fetchAll(
        'SELECT * FROM penyakit WHERE nama_penyakit LIKE ? OR kode_penyakit LIKE ? ORDER BY id_penyakit DESC LIMIT ? OFFSET ?',
        ['%' . $search . '%', '%' . $search . '%', $limit, $offset]
    );
    $total = fetchOne('SELECT COUNT(*) as count FROM penyakit WHERE nama_penyakit LIKE ? OR kode_penyakit LIKE ?', ['%' . $search . '%', '%' . $search . '%'])['count'];
} else {
    $penyakit_list = fetchAll('SELECT * FROM penyakit ORDER BY id_penyakit DESC LIMIT ? OFFSET ?', [$limit, $offset]);
    $total = fetchOne('SELECT COUNT(*) as count FROM penyakit')['count'];
}

$total_pages = ceil($total / $limit);
$total_penyakit = fetchOne('SELECT COUNT(*) as count FROM penyakit')['count'];

$page_title = 'Manajemen Penyakit Kulit';
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
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }
    </style>
    <link href="assets/admin-theme.css?v=20260731-5" rel="stylesheet">
</head>
<body class="admin-shell admin-sidebar-shell bg-background text-on-surface font-body-md">

<?php require __DIR__ . '/includes/sidebar.php'; ?>

<!-- Header -->
<header class="bg-primary-container sticky top-0 shadow-sm z-40">
    <div class="flex justify-between items-center px-6 py-4 max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-on-primary">Manajemen Penyakit</h1>
        </div>
        <div class="admin-header-actions">
            <a href="../logout.php" onclick="confirmLogout('../logout.php', event)" class="admin-header-logout">Keluar</a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-8">
    
    <!-- Breadcrumb -->
    <nav class="flex justify-between items-center text-sm mb-6 gap-2">
        <div class="flex items-center gap-2">
            <a href="dashboard.php" class="text-secondary hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">home</span>
                Admin
            </a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-primary font-semibold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">coronavirus</span>
                Manajemen Penyakit
            </span>
        </div>
    </nav>

    <!-- Alert Messages -->
    <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
            <p class="text-green-700 font-semibold"><?php echo $success; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <p class="text-red-700 font-semibold"><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <!-- Title Section -->
    <section class="mb-8">
        <h2 class="text-3xl font-bold mb-2">Manajemen Penyakit</h2>
        <p class="text-gray-600">Kelola daftar penyakit untuk sistem pakar diagnosis</p>
    </section>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <div class="flex items-center gap-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">coronavirus</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">TOTAL PENYAKIT</p>
                    <p class="text-3xl font-bold"><?php echo $total_penyakit; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <div class="flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">verified</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">STATUS</p>
                    <p class="text-xl font-bold">Aktif</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <div class="flex items-center gap-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">category</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">KATEGORI</p>
                    <p class="text-xl font-bold">Kulit</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Button -->
    <div class="mb-6">
        <a href="tambah_penyakit.php" class="inline-flex bg-secondary text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold items-center gap-2">
            <span class="material-symbols-outlined">add</span>
            Tambah Data Penyakit & Gejala
        </a>
    </div>

    <!-- Search & Filter -->
    <div class="mb-6 flex gap-4">
        <form method="GET" class="flex-1 flex gap-2">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari Kode atau Nama Penyakit..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary">
            </div>
            <button type="submit" class="bg-secondary text-white px-6 py-2 rounded-lg hover:bg-blue-700">Cari</button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-x-auto overscroll-x-contain">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">KODE</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">NAMA PENYAKIT</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">NAMA LATIN</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">DESKRIPSI</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($penyakit_list)): ?>
                    <?php foreach ($penyakit_list as $penyakit): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    <?php echo htmlspecialchars($penyakit['kode_penyakit']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($penyakit['nama_penyakit']); ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($penyakit['nama_latin'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars(substr($penyakit['deskripsi'] ?? '', 0, 50)) . (strlen($penyakit['deskripsi'] ?? '') > 50 ? '...' : ''); ?></td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" title="Edit penyakit" aria-label="Edit penyakit" onclick='openEditModal(<?php echo (int) $penyakit['id_penyakit']; ?>, <?php echo htmlspecialchars(json_encode($penyakit['kode_penyakit']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($penyakit['nama_penyakit']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($penyakit['nama_latin']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($penyakit['deskripsi']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($penyakit['solusi']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)' class="text-blue-600 hover:text-blue-800 mr-4">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button type="button" title="Hapus penyakit" aria-label="Hapus penyakit" onclick="deletePenyakit(<?php echo $penyakit['id_penyakit']; ?>)" class="text-red-600 hover:text-red-800">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data penyakit</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex justify-center gap-2 mt-8">
            <?php if ($page > 1): ?>
                <a href="?page=1" class="px-3 py-2 border rounded hover:bg-gray-100">« Awal</a>
                <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 border rounded hover:bg-gray-100">‹ Sebelumnya</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="px-3 py-2 border rounded <?php echo $i === $page ? 'bg-secondary text-white' : 'hover:bg-gray-100'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 border rounded hover:bg-gray-100">Selanjutnya ›</a>
                <a href="?page=<?php echo $total_pages; ?>" class="px-3 py-2 border rounded hover:bg-gray-100">Akhir »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Add/Edit Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6"></h2>
        
        <form id="modalForm" method="POST" class="space-y-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id_penyakit" id="id_penyakit">
            <input type="hidden" name="action" id="action" value="add">
            
            <div>
                <label class="block text-sm font-semibold mb-2">Kode Penyakit</label>
                <div class="flex gap-2">
                    <input type="text" name="kode_penyakit" id="kode_penyakit" required class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="Contoh: P031">
                    <button type="button" onclick="autoGenerateCode()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 whitespace-nowrap">
                        <span class="material-symbols-outlined text-sm">auto_awesome</span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Saran: <span id="suggested-code"><?php echo $suggested_code; ?></span></p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Nama Penyakit</label>
                <input type="text" name="nama_penyakit" id="nama_penyakit" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="Contoh: Kudis">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Nama Latin (Opsional)</label>
                <input type="text" name="nama_latin" id="nama_latin" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="Contoh: Knemidocoptes mutans">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="Jelaskan tentang penyakit ini..."></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Solusi/Penanganan</label>
                <textarea name="solusi" id="solusi" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary" placeholder="Bagaimana cara menangani penyakit ini..."></textarea>
            </div>
            
            <div class="flex gap-4 justify-end pt-4 border-t">
                <button type="button" onclick="closeModal()" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">Batal</button>
                <button type="submit" class="px-6 py-2 bg-secondary text-white rounded-lg hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function autoGenerateCode() {
    const suggestedCode = document.getElementById('suggested-code').textContent;
    document.getElementById('kode_penyakit').value = suggestedCode;
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Penyakit Baru';
    document.getElementById('action').value = 'add';
    document.getElementById('modalForm').reset();
    document.getElementById('id_penyakit').value = '';
    document.getElementById('modal').classList.remove('hidden');
}

function openEditModal(id, kode, nama, latin, deskripsi, solusi) {
    document.getElementById('modalTitle').textContent = 'Edit Penyakit';
    document.getElementById('action').value = 'edit';
    document.getElementById('id_penyakit').value = id;
    document.getElementById('kode_penyakit').value = kode;
    document.getElementById('nama_penyakit').value = nama;
    document.getElementById('nama_latin').value = latin;
    document.getElementById('deskripsi').value = deskripsi;
    document.getElementById('solusi').value = solusi;
    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

function deletePenyakit(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('modal');
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === modal) {
        modal.classList.add('hidden');
    }
    if (event.target === deleteModal) {
        deleteModal.classList.add('hidden');
    }
});
</script>

<!-- Delete Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-sm w-full mx-4">
        <h3 class="text-xl font-bold mb-4">Konfirmasi Hapus</h3>
        <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus penyakit ini?</p>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_penyakit" id="deleteId">
            
            <div class="flex gap-3">
                <button type="button" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50" onclick="document.getElementById('deleteModal').classList.add('hidden')">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">Hapus</button>
            </div>
        </form>
    </div>
</div>

<?php $logout_url = '../logout.php'; include __DIR__ . '/../includes/logout-confirmation.php'; ?>

</body>
</html>
