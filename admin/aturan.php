<?php
/**
 * Admin - Manajemen Aturan (Rule Base untuk Certainty Factor)
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
        $id_penyakit = intval($_POST['id_penyakit'] ?? 0);
        $id_gejala = intval($_POST['id_gejala'] ?? 0);
        $cf_pakar = floatval($_POST['cf_pakar'] ?? 0);
        
        // Validasi input
        if ($id_penyakit <= 0) {
            $error = 'Pilih penyakit terlebih dahulu';
        } elseif ($id_gejala <= 0) {
            $error = 'Pilih gejala terlebih dahulu';
        } elseif ($cf_pakar < 0 || $cf_pakar > 1) {
            $error = 'Nilai CF harus antara 0.0 dan 1.0';
        } else {
            try {
                // Cek apakah aturan sudah ada
                $existing = fetchOne(
                    'SELECT id_aturan FROM aturan WHERE id_penyakit = ? AND id_gejala = ?',
                    [$id_penyakit, $id_gejala]
                );
                
                if ($existing) {
                    $error = 'Aturan untuk kombinasi penyakit dan gejala ini sudah ada';
                } else {
                    $result = execute(
                        'INSERT INTO aturan (id_penyakit, id_gejala, cf_pakar) VALUES (?, ?, ?)',
                        [$id_penyakit, $id_gejala, $cf_pakar]
                    );
                    
                    if ($result > 0) {
                        $success = 'Aturan berhasil ditambahkan';
                        header("Refresh:1; url=aturan.php");
                    } else {
                        $error = 'Gagal menambahkan aturan';
                    }
                }
            } catch (Exception $e) {
                error_log('Rule insert failed: ' . $e->getMessage());
                $error = 'Aturan belum dapat disimpan';
            }
        }
    } elseif ($action === 'edit') {
        $id_aturan = intval($_POST['id_aturan'] ?? 0);
        $cf_pakar = floatval($_POST['cf_pakar'] ?? 0);
        
        // Validasi input
        if ($id_aturan <= 0) {
            $error = 'ID aturan tidak valid';
        } elseif ($cf_pakar < 0 || $cf_pakar > 1) {
            $error = 'Nilai CF harus antara 0.0 dan 1.0';
        } else {
            try {
                $result = execute(
                    'UPDATE aturan SET cf_pakar = ? WHERE id_aturan = ?',
                    [$cf_pakar, $id_aturan]
                );
                
                if ($result > 0) {
                    $success = 'Aturan berhasil diperbarui';
                    header("Refresh:1; url=aturan.php");
                } else {
                    $error = 'Tidak ada perubahan atau ID aturan tidak ditemukan';
                }
            } catch (Exception $e) {
                error_log('Rule update failed: ' . $e->getMessage());
                $error = 'Aturan belum dapat diperbarui';
            }
        }
    } elseif ($action === 'delete') {
        $id_aturan = intval($_POST['id_aturan'] ?? 0);
        
        if ($id_aturan <= 0) {
            $error = 'ID aturan tidak valid';
        } else {
            try {
                $result = execute('DELETE FROM aturan WHERE id_aturan = ?', [$id_aturan]);
                
                if ($result > 0) {
                    $success = 'Aturan berhasil dihapus';
                    header("Refresh:1; url=aturan.php");
                } else {
                    $error = 'Aturan tidak ditemukan';
                }
            } catch (Exception $e) {
                error_log('Rule delete failed: ' . $e->getMessage());
                $error = 'Aturan belum dapat dihapus';
            }
        }
    }
}

// Get all data
$penyakit_list = fetchAll('SELECT id_penyakit, kode_penyakit, nama_penyakit FROM penyakit ORDER BY kode_penyakit ASC');
$gejala_list = fetchAll('SELECT id_gejala, kode_gejala, nama_gejala FROM gejala ORDER BY kode_gejala ASC');

// Get aturan dengan join
$search = limitedText($_GET['search'] ?? '', 100);
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$limit = 10;
$offset = ($page - 1) * $limit;

if ($search) {
    $aturan_list = fetchAll(
        'SELECT a.id_aturan, a.cf_pakar, p.kode_penyakit, p.nama_penyakit, g.kode_gejala, g.nama_gejala 
         FROM aturan a 
         JOIN penyakit p ON a.id_penyakit = p.id_penyakit 
         JOIN gejala g ON a.id_gejala = g.id_gejala 
         WHERE p.nama_penyakit LIKE ? OR g.nama_gejala LIKE ? OR p.kode_penyakit LIKE ? OR g.kode_gejala LIKE ?
         ORDER BY p.kode_penyakit, g.kode_gejala DESC LIMIT ? OFFSET ?',
        ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%', $limit, $offset]
    );
    $total = fetchOne(
        'SELECT COUNT(*) as count FROM aturan a 
         JOIN penyakit p ON a.id_penyakit = p.id_penyakit 
         JOIN gejala g ON a.id_gejala = g.id_gejala 
         WHERE p.nama_penyakit LIKE ? OR g.nama_gejala LIKE ? OR p.kode_penyakit LIKE ? OR g.kode_gejala LIKE ?',
        ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%']
    )['count'];
} else {
    $aturan_list = fetchAll(
        'SELECT a.id_aturan, a.cf_pakar, p.kode_penyakit, p.nama_penyakit, g.kode_gejala, g.nama_gejala 
         FROM aturan a 
         JOIN penyakit p ON a.id_penyakit = p.id_penyakit 
         JOIN gejala g ON a.id_gejala = g.id_gejala 
         ORDER BY p.kode_penyakit, g.kode_gejala DESC LIMIT ? OFFSET ?',
        [$limit, $offset]
    );
    $total = fetchOne('SELECT COUNT(*) as count FROM aturan')['count'];
}

$total_pages = ceil($total / $limit);
$total_aturan = fetchOne('SELECT COUNT(*) as count FROM aturan')['count'];

$page_title = 'Manajemen Aturan (Rule Base)';
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
    <link href="assets/admin-theme.css?v=20260731-5" rel="stylesheet">
</head>
<body class="admin-shell admin-sidebar-shell bg-background text-on-surface font-body-md">

<?php require __DIR__ . '/includes/sidebar.php'; ?>

<!-- Header -->
<header class="bg-green-600 text-white sticky top-0 shadow-sm z-40">
    <div class="flex justify-between items-center px-6 py-4 max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-white">Manajemen Aturan (CF)</h1>
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
                <span class="material-symbols-outlined text-sm">rule</span>
                Manajemen Aturan
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
        <h2 class="text-3xl font-bold mb-2">Manajemen Aturan (Rule Base)</h2>
        <p class="text-gray-600">Kelola relasi penyakit-gejala dengan Certainty Factor untuk sistem pakar</p>
    </section>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <div class="flex items-center gap-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600 text-2xl">rule</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">TOTAL ATURAN</p>
                    <p class="text-3xl font-bold"><?php echo $total_aturan; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <div class="flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600 text-2xl">verified</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">PENYAKIT</p>
                    <p class="text-3xl font-bold"><?php echo count($penyakit_list); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <div class="flex items-center gap-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600 text-2xl">checklist</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">GEJALA</p>
                    <p class="text-3xl font-bold"><?php echo count($gejala_list); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Button -->
    <div class="mb-6">
        <button onclick="openAddModal()" class="bg-secondary text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined">add</span>
            Tambah Aturan Baru
        </button>
    </div>

    <!-- Search & Filter -->
    <div class="mb-6 flex gap-4">
        <form method="GET" class="flex-1 flex gap-2">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari penyakit atau gejala..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary">
            </div>
            <button type="submit" class="bg-secondary text-white px-6 py-2 rounded-lg hover:bg-blue-700">Cari</button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-x-auto overscroll-x-contain">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">PENYAKIT</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">GEJALA</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">CF VALUE</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($aturan_list)): ?>
                    <?php foreach ($aturan_list as $aturan): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex gap-2 items-start">
                                    <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold whitespace-nowrap">
                                        <?php echo htmlspecialchars($aturan['kode_penyakit']); ?>
                                    </span>
                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($aturan['nama_penyakit']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2 items-start">
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold whitespace-nowrap">
                                        <?php echo htmlspecialchars($aturan['kode_gejala']); ?>
                                    </span>
                                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($aturan['nama_gejala']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-bold">
                                    <?php echo number_format($aturan['cf_pakar'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" title="Edit aturan" aria-label="Edit aturan" onclick="openEditModal(<?php echo $aturan['id_aturan']; ?>, <?php echo $aturan['cf_pakar']; ?>)" class="text-blue-600 hover:text-blue-800 mr-4">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button type="button" title="Hapus aturan" aria-label="Hapus aturan" onclick="deleteAturan(<?php echo $aturan['id_aturan']; ?>)" class="text-red-600 hover:text-red-800">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">Tidak ada aturan ditemukan</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-6">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-2 border rounded hover:bg-gray-100">←</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-2 rounded <?php echo $i === $page ? 'bg-secondary text-white' : 'border hover:bg-gray-100'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-2 border rounded hover:bg-gray-100">→</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<!-- Add/Edit Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6"></h2>
        
        <form id="modalForm" method="POST" class="space-y-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id_aturan" id="id_aturan">
            <input type="hidden" name="action" id="action" value="add">
            
            <div id="penyakitContainer">
                <label class="block text-sm font-semibold mb-2">Pilih Penyakit</label>
                <select name="id_penyakit" id="id_penyakit" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary">
                    <option value="">-- Pilih Penyakit --</option>
                    <?php foreach ($penyakit_list as $p): ?>
                        <option value="<?php echo $p['id_penyakit']; ?>"><?php echo htmlspecialchars($p['kode_penyakit'] . ' - ' . $p['nama_penyakit']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="gejalaSContainer">
                <label class="block text-sm font-semibold mb-2">Pilih Gejala</label>
                <select name="id_gejala" id="id_gejala" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary">
                    <option value="">-- Pilih Gejala --</option>
                    <?php foreach ($gejala_list as $g): ?>
                        <option value="<?php echo $g['id_gejala']; ?>"><?php echo htmlspecialchars($g['kode_gejala'] . ' - ' . $g['nama_gejala']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Certainty Factor (0.0 - 1.0)</label>
                <div class="flex items-center gap-4">
                    <input type="range" name="cf_pakar" id="cf_pakar" min="0" max="1" step="0.01" value="0.5" class="flex-1" oninput="updateCFValue(this.value)">
                    <input type="text" id="cfValue" value="0.50" readonly class="w-16 px-3 py-2 border border-gray-300 rounded-lg text-center font-semibold text-secondary">
                </div>
                <p class="text-xs text-gray-500 mt-2">Interpretasi: 0 = Tidak Yakin, 0.5 = Cukup Yakin, 1.0 = Sangat Yakin</p>
            </div>
            
            <div class="flex gap-4 justify-end pt-4 border-t">
                <button type="button" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100" onclick="document.getElementById('modal').classList.add('hidden')">Batal</button>
                <button type="submit" class="px-6 py-2 bg-secondary text-white rounded-lg hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-sm w-full mx-4">
        <h3 class="text-xl font-bold mb-4">Konfirmasi Hapus</h3>
        <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus aturan ini?</p>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_aturan" id="deleteId">
            
            <div class="flex gap-3">
                <button type="button" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50" onclick="document.getElementById('deleteModal').classList.add('hidden')">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateCFValue(value) {
    document.getElementById('cfValue').value = parseFloat(value).toFixed(2);
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Aturan Baru';
    document.getElementById('action').value = 'add';
    document.getElementById('id_aturan').value = '';
    document.getElementById('penyakitContainer').style.display = 'block';
    document.getElementById('gejalaSContainer').style.display = 'block';
    document.getElementById('id_penyakit').value = '';
    document.getElementById('id_gejala').value = '';
    document.getElementById('cf_pakar').value = 0.5;
    document.getElementById('cfValue').value = '0.50';
    document.getElementById('modal').classList.remove('hidden');
}

function openEditModal(id, cfValue) {
    document.getElementById('modalTitle').textContent = 'Edit Nilai CF';
    document.getElementById('action').value = 'edit';
    document.getElementById('id_aturan').value = id;
    document.getElementById('penyakitContainer').style.display = 'none';
    document.getElementById('gejalaSContainer').style.display = 'none';
    document.getElementById('cf_pakar').value = cfValue;
    document.getElementById('cfValue').value = parseFloat(cfValue).toFixed(2);
    document.getElementById('modal').classList.remove('hidden');
}

function deleteAturan(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}
</script>

<script>
document.addEventListener('click', function(event) {
    const modal = document.getElementById('modal');
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === modal && modal) {
        modal.classList.add('hidden');
    }
    if (event.target === deleteModal && deleteModal) {
        deleteModal.classList.add('hidden');
    }
});
</script>

<?php $logout_url = '../logout.php'; include __DIR__ . '/../includes/logout-confirmation.php'; ?>

</body>
</html>
