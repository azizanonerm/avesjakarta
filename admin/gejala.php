<?php
/**
 * Admin - Manajemen Gejala
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
        $kode_gejala = strtoupper(limitedText($_POST['kode_gejala'] ?? '', 10));
        $nama_gejala = limitedText($_POST['nama_gejala'] ?? '', 150);
        $deskripsi = limitedText($_POST['deskripsi'] ?? '', 5000);
        
        // Validasi input
        if (empty($kode_gejala)) {
            $error = 'Kode gejala harus diisi';
        } elseif (!preg_match('/^G\d{3}$/', $kode_gejala)) {
            $error = 'Kode gejala harus memakai format G001';
        } elseif (empty($nama_gejala)) {
            $error = 'Nama gejala harus diisi';
        } elseif (strlen($kode_gejala) > 10) {
            $error = 'Kode gejala maksimal 10 karakter';
        } elseif (strlen($nama_gejala) > 150) {
            $error = 'Nama gejala maksimal 150 karakter';
        } else {
            try {
                $result = execute(
                    'INSERT INTO gejala (kode_gejala, nama_gejala, deskripsi) VALUES (?, ?, ?)',
                    [$kode_gejala, $nama_gejala, $deskripsi]
                );
                if ($result > 0) {
                    $success = 'Gejala berhasil ditambahkan';
                    header("Refresh:1; url=gejala.php");
                } else {
                    $error = 'Gagal menambahkan gejala';
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = 'Kode gejala sudah ada, gunakan kode yang berbeda';
                } else {
                    error_log('Symptom insert failed: ' . $e->getMessage());
                    $error = 'Data gejala belum dapat disimpan';
                }
            } catch (Exception $e) {
                error_log('Symptom insert failed: ' . $e->getMessage());
                $error = 'Data gejala belum dapat disimpan';
            }
        }
    } elseif ($action === 'edit') {
        $id_gejala = intval($_POST['id_gejala'] ?? 0);
        $kode_gejala = strtoupper(limitedText($_POST['kode_gejala'] ?? '', 10));
        $nama_gejala = limitedText($_POST['nama_gejala'] ?? '', 150);
        $deskripsi = limitedText($_POST['deskripsi'] ?? '', 5000);
        
        // Validasi input
        if ($id_gejala <= 0) {
            $error = 'ID gejala tidak valid';
        } elseif (empty($kode_gejala)) {
            $error = 'Kode gejala harus diisi';
        } elseif (!preg_match('/^G\d{3}$/', $kode_gejala)) {
            $error = 'Kode gejala harus memakai format G001';
        } elseif (empty($nama_gejala)) {
            $error = 'Nama gejala harus diisi';
        } elseif (strlen($kode_gejala) > 10) {
            $error = 'Kode gejala maksimal 10 karakter';
        } elseif (strlen($nama_gejala) > 150) {
            $error = 'Nama gejala maksimal 150 karakter';
        } else {
            try {
                $result = execute(
                    'UPDATE gejala SET kode_gejala = ?, nama_gejala = ?, deskripsi = ? WHERE id_gejala = ?',
                    [$kode_gejala, $nama_gejala, $deskripsi, $id_gejala]
                );
                if ($result > 0) {
                    $success = 'Gejala berhasil diperbarui';
                    header("Refresh:1; url=gejala.php");
                } else {
                    $error = 'Tidak ada perubahan atau ID gejala tidak ditemukan';
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = 'Kode gejala sudah ada, gunakan kode yang berbeda';
                } else {
                    error_log('Symptom update failed: ' . $e->getMessage());
                    $error = 'Data gejala belum dapat diperbarui';
                }
            } catch (Exception $e) {
                error_log('Symptom update failed: ' . $e->getMessage());
                $error = 'Data gejala belum dapat diperbarui';
            }
        }
    } elseif ($action === 'delete') {
        $id_gejala = intval($_POST['id_gejala'] ?? 0);
        
        if ($id_gejala <= 0) {
            $error = 'ID gejala tidak valid';
        } else {
            try {
                // Hapus aturan terkait terlebih dahulu (CASCADE sudah ada di DB, tapi double-check)
                execute('DELETE FROM aturan WHERE id_gejala = ?', [$id_gejala]);
                
                // Kemudian hapus gejala
                $result = execute('DELETE FROM gejala WHERE id_gejala = ?', [$id_gejala]);
                
                if ($result > 0) {
                    $success = 'Gejala berhasil dihapus';
                    header("Refresh:1; url=gejala.php");
                } else {
                    $error = 'Gejala tidak ditemukan';
                }
            } catch (Exception $e) {
                error_log('Symptom delete failed: ' . $e->getMessage());
                $error = 'Data gejala belum dapat dihapus';
            }
        }
    }
}

// Generate kode gejala otomatis
function generateKodeGejala() {
    $last_gejala = fetchOne('SELECT kode_gejala FROM gejala ORDER BY id_gejala DESC LIMIT 1');
    if ($last_gejala) {
        $last_code = $last_gejala['kode_gejala'];
        if (preg_match('/G(\d+)/', $last_code, $matches)) {
            $next_num = intval($matches[1]) + 1;
            return 'G' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        }
    }
    return 'G001';
}

$suggested_code = generateKodeGejala();

// Get list gejala
$search = limitedText($_GET['search'] ?? '', 100);
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$limit = 10;
$offset = ($page - 1) * $limit;

if ($search) {
    $gejala_list = fetchAll(
        'SELECT * FROM gejala WHERE nama_gejala LIKE ? OR kode_gejala LIKE ? ORDER BY id_gejala DESC LIMIT ? OFFSET ?',
        ['%' . $search . '%', '%' . $search . '%', $limit, $offset]
    );
    $total = fetchOne('SELECT COUNT(*) as count FROM gejala WHERE nama_gejala LIKE ? OR kode_gejala LIKE ?', ['%' . $search . '%', '%' . $search . '%'])['count'];
} else {
    $gejala_list = fetchAll('SELECT * FROM gejala ORDER BY id_gejala DESC LIMIT ? OFFSET ?', [$limit, $offset]);
    $total = fetchOne('SELECT COUNT(*) as count FROM gejala')['count'];
}

$total_pages = ceil($total / $limit);
$total_gejala = fetchOne('SELECT COUNT(*) as count FROM gejala')['count'];

$page_title = 'Manajemen Gejala';
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
            <h1 class="text-2xl font-bold text-white">Manajemen Gejala</h1>
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
                <span class="material-symbols-outlined text-sm">checklist</span>
                Manajemen Gejala
            </span>
        </div>
    </nav>

    <!-- Alert Messages -->
    <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <p class="text-green-700"><?php echo $success; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-red-700"><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <!-- Title Section -->
    <section class="mb-8">
        <h2 class="text-3xl font-bold mb-2">Manajemen Gejala</h2>
        <p class="text-gray-600">Kelola daftar gejala klinis untuk sistem pakar diagnosis burung</p>
    </section>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <div class="flex items-center gap-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600">format_list_bulleted</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">TOTAL GEJALA</p>
                    <p class="text-3xl font-bold"><?php echo $total_gejala; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <div class="flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600">schedule</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">UPDATE TERAKHIR</p>
                    <p class="text-xl font-bold">Hari Ini</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <div class="flex items-center gap-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600">check_circle</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">GEJALA AKTIF</p>
                    <p class="text-3xl font-bold">100%</p>
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
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari Kode atau Nama Gejala..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary">
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
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">NAMA GEJALA</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">DESKRIPSI</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($gejala_list)): ?>
                    <?php foreach ($gejala_list as $gejala): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    <?php echo htmlspecialchars($gejala['kode_gejala']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($gejala['nama_gejala']); ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars(substr($gejala['deskripsi'], 0, 50)) . (strlen($gejala['deskripsi']) > 50 ? '...' : ''); ?></td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" title="Edit gejala" aria-label="Edit gejala" onclick='openEditModal(<?php echo (int) $gejala['id_gejala']; ?>, <?php echo htmlspecialchars(json_encode($gejala['kode_gejala']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($gejala['nama_gejala']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($gejala['deskripsi']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)' class="text-blue-600 hover:text-blue-800 mr-4">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button type="button" title="Hapus gejala" aria-label="Hapus gejala" onclick="deleteGejala(<?php echo $gejala['id_gejala']; ?>)" class="text-red-600 hover:text-red-800">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">Tidak ada gejala ditemukan</td>
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

<!-- Modal Add/Edit -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6">Tambah Gejala Baru</h2>
        
        <form method="POST" id="modalForm" class="space-y-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="id_gejala" id="id_gejala">
            
            <div>
                <label class="block text-sm font-semibold mb-2">Kode Gejala</label>
                <div class="flex gap-2">
                    <input type="text" name="kode_gejala" id="kode_gejala" required class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary">
                    <button type="button" onclick="autoGenerateCode()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 whitespace-nowrap">
                        <span class="material-symbols-outlined text-sm">auto_awesome</span>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Saran: <span id="suggested-code"><?php echo $suggested_code; ?></span></p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Nama Gejala</label>
                <input type="text" name="nama_gejala" id="nama_gejala" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary">
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary"></textarea>
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
        <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus gejala ini?</p>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_gejala" id="deleteId">
            
            <div class="flex gap-3">
                <button type="button" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50" onclick="document.getElementById('deleteModal').classList.add('hidden')">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function autoGenerateCode() {
    const suggestedCode = document.getElementById('suggested-code').textContent;
    document.getElementById('kode_gejala').value = suggestedCode;
}

function openAddModal() {
    document.getElementById('action').value = 'add';
    document.getElementById('modalTitle').textContent = 'Tambah Gejala Baru';
    document.getElementById('id_gejala').value = '';
    document.getElementById('kode_gejala').value = '';
    document.getElementById('nama_gejala').value = '';
    document.getElementById('deskripsi').value = '';
    document.getElementById('modal').classList.remove('hidden');
}

function openEditModal(id, kode, nama, deskripsi) {
    document.getElementById('action').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Gejala';
    document.getElementById('id_gejala').value = id;
    document.getElementById('kode_gejala').value = kode;
    document.getElementById('nama_gejala').value = nama;
    document.getElementById('deskripsi').value = deskripsi;
    document.getElementById('modal').classList.remove('hidden');
}

function deleteGejala(id) {
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
