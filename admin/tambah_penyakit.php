<?php
require_once __DIR__ . '/../config/security.php';
requireAdminUser('../login.php');

require_once '../config/database.php';

$error = '';
$success = '';

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

$suggested_code_penyakit = generateKodePenyakit();
$suggested_code_gejala = generateKodeGejala();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();
    $kode_penyakit = strtoupper(limitedText($_POST['kode_penyakit'] ?? '', 10));
    $nama_penyakit = limitedText($_POST['nama_penyakit'] ?? '', 100);
    $nama_latin = limitedText($_POST['nama_latin'] ?? '', 150);
    $deskripsi = limitedText($_POST['deskripsi'] ?? '', 5000);
    $solusi = limitedText($_POST['solusi'] ?? '', 5000);
    $gejala_baru = $_POST['gejala_baru'] ?? [];
    
    if (empty($kode_penyakit)) {
        $error = 'Kode penyakit harus diisi';
    } elseif (!preg_match('/^P\d{3}$/', $kode_penyakit)) {
        $error = 'Kode penyakit harus memakai format P001';
    } elseif (empty($nama_penyakit)) {
        $error = 'Nama penyakit harus diisi';
    } elseif (!is_array($gejala_baru) || empty($gejala_baru)) {
        $error = 'Tambahkan minimal 1 gejala untuk penyakit ini';
    } elseif (count($gejala_baru) > 20) {
        $error = 'Maksimal 20 gejala dapat ditambahkan dalam satu proses';
    } else {
        try {
            $pdo->beginTransaction();
            
            $result = execute(
                'INSERT INTO penyakit (kode_penyakit, nama_penyakit, nama_latin, deskripsi, solusi) VALUES (?, ?, ?, ?, ?)',
                [$kode_penyakit, $nama_penyakit, $nama_latin, $deskripsi, $solusi]
            );
            
            if ($result > 0) {
                $id_penyakit = $pdo->lastInsertId();
                $count_gejala = 0;
                
                foreach ($gejala_baru as $index => $gejala_data) {
                    if (!is_array($gejala_data)) {
                        continue;
                    }

                    $kode_gejala = strtoupper(limitedText($gejala_data['kode'] ?? '', 10));
                    $nama_gejala = limitedText($gejala_data['nama'] ?? '', 150);
                    $deskripsi_gejala = limitedText($gejala_data['deskripsi'] ?? '', 5000);
                    $cf_value = floatval($gejala_data['cf'] ?? 0);
                    
                    if (
                        empty($nama_gejala)
                        || !preg_match('/^G\d{3}$/', $kode_gejala)
                        || $cf_value < 0
                        || $cf_value > 1
                    ) {
                        continue;
                    }
                    
                    $existing_gejala = fetchOne('SELECT id_gejala FROM gejala WHERE kode_gejala = ?', [$kode_gejala]);
                    
                    if ($existing_gejala) {
                        $id_gejala = $existing_gejala['id_gejala'];
                    } else {
                        execute(
                            'INSERT INTO gejala (kode_gejala, nama_gejala, deskripsi) VALUES (?, ?, ?)',
                            [$kode_gejala, $nama_gejala, $deskripsi_gejala]
                        );
                        $id_gejala = $pdo->lastInsertId();
                    }
                    
                    if ($cf_value > 0) {
                        execute(
                            'INSERT INTO aturan (id_penyakit, id_gejala, cf_pakar) VALUES (?, ?, ?)',
                            [$id_penyakit, $id_gejala, $cf_value]
                        );
                        $count_gejala++;
                    }
                }
                
                $pdo->commit();
                $success = "Berhasil! Penyakit ditambahkan dengan $count_gejala gejala baru!";
                header("Refresh:2; url=penyakit.php");
            } else {
                $pdo->rollBack();
                $error = 'Gagal menambahkan penyakit';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'Kode penyakit atau gejala sudah ada';
            } else {
                error_log('Disease bundle insert failed: ' . $e->getMessage());
                $error = 'Data penyakit belum dapat disimpan';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Disease bundle insert failed: ' . $e->getMessage());
            $error = 'Data penyakit belum dapat disimpan';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title>Tambah Penyakit & Gejala Baru - AVES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="assets/admin-theme.css?v=20260731-5" rel="stylesheet">
</head>
<body class="admin-shell admin-sidebar-shell bg-gray-50">

<?php require __DIR__ . '/includes/sidebar.php'; ?>

<header class="bg-blue-800 text-white sticky top-0 shadow-md z-40">
    <div class="flex justify-between items-center px-6 py-4 max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold">Tambah Penyakit & Gejala Baru</h1>
        </div>
        <div class="admin-header-actions">
            <a href="../logout.php" onclick="confirmLogout('../logout.php', event)" class="admin-header-logout">Keluar</a>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-6 py-8">
    
    <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <p class="text-green-700 font-semibold"><?php echo $success; ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">error</span>
                <p class="text-red-700 font-semibold"><?php echo $error; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-blue-50 border-l-4 border-blue-600 p-4 mb-6 rounded">
        <div class="flex items-start gap-2">
            <span class="material-symbols-outlined text-blue-600">info</span>
            <div>
                <p class="font-semibold text-blue-900">Input Penyakit & Gejala Baru Sekaligus</p>
                <p class="text-sm text-blue-700 mt-1">Isi data penyakit, lalu tambahkan gejala-gejala baru dengan mengetik nama gejala.</p>
            </div>
        </div>
    </div>

    <form method="POST" class="space-y-6">
        <?php echo csrfField(); ?>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">medical_services</span>
                Data Penyakit Baru
            </h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Kode Penyakit <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" name="kode_penyakit" id="kode_penyakit" required 
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" 
                               placeholder="P031" value="<?php echo htmlspecialchars($suggested_code_penyakit); ?>">
                        <button type="button" onclick="autoGenerateKodePenyakit()" 
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            <span class="material-symbols-outlined text-sm">auto_awesome</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Saran: <span id="suggested-code-penyakit"><?php echo $suggested_code_penyakit; ?></span></p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Nama Penyakit <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_penyakit" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg" 
                           placeholder="Contoh: Kudis">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Nama Latin (Opsional)</label>
                    <input type="text" name="nama_latin" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg" 
                           placeholder="Contoh: Knemidocoptes mutans">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg" 
                              placeholder="Jelaskan tentang penyakit ini..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Solusi/Penanganan</label>
                    <textarea name="solusi" rows="3" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg" 
                              placeholder="Bagaimana cara menangani penyakit ini..."></textarea>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 rounded-xl shadow-md p-6 border border-blue-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold flex items-center gap-2 text-blue-800">
                    <span class="material-symbols-outlined text-3xl">add_circle</span>
                    INPUT GEJALA BARU (KETIK DI SINI)
                </h2>
                <button type="button" onclick="tambahGejala()" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2 font-bold shadow-md">
                    <span class="material-symbols-outlined">add</span>
                    Tambah Gejala Lagi
                </button>
            </div>
            
            <div class="bg-white border-l-4 border-blue-600 p-4 mb-4 rounded-lg shadow-sm">
                <p class="font-bold text-blue-900 text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">info</span>
                    Cara Menggunakan
                </p>
                <p class="text-sm text-slate-600 mt-2">1. Ketik nama gejala baru pada kolom yang tersedia.</p>
                <p class="text-sm text-slate-600">2. Klik “Tambah Gejala Lagi” untuk menambahkan gejala lainnya.</p>
                <p class="text-sm text-slate-600">3. Minimal satu gejala harus diisi.</p>
            </div>
            
            <div id="gejalaContainer" class="space-y-4">
                <div class="gejala-item border border-blue-200 rounded-xl p-5 bg-white shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-bold text-lg text-blue-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600">assignment</span>
                            <span class="gejala-number">Gejala #1</span>
                        </h3>
                        <button type="button" onclick="hapusGejala(this)" class="text-red-600 hover:text-red-800 px-3 py-1 rounded hover:bg-red-50">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold mb-2">Kode Gejala <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <input type="text" name="gejala_baru[0][kode]" required 
                                       class="flex-1 px-4 py-3 border border-slate-300 rounded-lg" 
                                       placeholder="G061" value="<?php echo htmlspecialchars($suggested_code_gejala); ?>">
                                <button type="button" onclick="autoGenerateKodeGejala(this)" 
                                        class="px-4 py-3 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 font-semibold">
                                    <span class="material-symbols-outlined">auto_awesome</span>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold mb-2">Nilai CF (0.0 - 1.0) <span class="text-red-500">*</span></label>
                            <input type="number" name="gejala_baru[0][cf]" min="0" max="1" step="0.1" value="0.7" required
                                   class="w-full px-4 py-3 border border-slate-300 rounded-lg">
                        </div>
                    </div>
                    
                    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <label class="block text-base font-bold mb-3 text-blue-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600">edit</span>
                            Nama Gejala Baru <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="gejala_baru[0][nama]" required 
                               class="w-full px-4 py-4 border border-blue-300 rounded-lg text-lg font-semibold bg-white" 
                               placeholder="Contoh: Bulu rontok berlebihan, Mata berair, Nafsu makan menurun">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold mb-2">Deskripsi Gejala (Opsional)</label>
                        <textarea name="gejala_baru[0][deskripsi]" rows="2" 
                                  class="w-full px-4 py-3 border border-slate-300 rounded-lg" 
                                  placeholder="Jelaskan detail gejala ini..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 bg-blue-50 p-4 rounded-lg">
                <p class="text-sm font-semibold mb-2">Panduan Nilai CF:</p>
                <ul class="text-xs text-gray-600 space-y-1">
                    <li>• <strong>0.1 - 0.3</strong> = Kemungkinan rendah</li>
                    <li>• <strong>0.4 - 0.6</strong> = Kemungkinan sedang</li>
                    <li>• <strong>0.7 - 0.9</strong> = Kemungkinan tinggi</li>
                    <li>• <strong>1.0</strong> = Sangat pasti</li>
                </ul>
            </div>
        </div>

        <div class="flex gap-4 justify-end">
            <a href="penyakit.php" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-100 font-semibold">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">save</span>
                Simpan Semua Data
            </button>
        </div>
    </form>

</main>

<script>
let gejalaCount = 1;
let nextKodeGejala = <?php echo intval(preg_replace('/[^0-9]/', '', $suggested_code_gejala)) + 1; ?>;

function autoGenerateKodePenyakit() {
    document.getElementById('kode_penyakit').value = document.getElementById('suggested-code-penyakit').textContent;
}

function autoGenerateKodeGejala(button) {
    const input = button.previousElementSibling;
    input.value = 'G' + String(nextKodeGejala).padStart(3, '0');
    nextKodeGejala++;
}

function tambahGejala() {
    const container = document.getElementById('gejalaContainer');
    const newIndex = gejalaCount;
    const kodeGejala = 'G' + String(nextKodeGejala).padStart(3, '0');
    
    const html = `
        <div class="gejala-item border border-blue-200 rounded-xl p-5 bg-white shadow-sm">
            <div class="flex justify-between items-start mb-4">
                <h3 class="font-bold text-lg text-blue-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">assignment</span>
                    <span class="gejala-number">Gejala #${newIndex + 1}</span>
                </h3>
                <button type="button" onclick="hapusGejala(this)" class="text-red-600 hover:text-red-800 px-3 py-1 rounded hover:bg-red-50">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold mb-2">Kode Gejala <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" name="gejala_baru[${newIndex}][kode]" required 
                               class="flex-1 px-4 py-3 border border-slate-300 rounded-lg" 
                               placeholder="G061" value="${kodeGejala}">
                        <button type="button" onclick="autoGenerateKodeGejala(this)" 
                                class="px-4 py-3 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 font-semibold">
                            <span class="material-symbols-outlined">auto_awesome</span>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-bold mb-2">Nilai CF (0.0 - 1.0) <span class="text-red-500">*</span></label>
                    <input type="number" name="gejala_baru[${newIndex}][cf]" min="0" max="1" step="0.1" value="0.7" required
                           class="w-full px-4 py-3 border border-slate-300 rounded-lg">
                </div>
            </div>
            
            <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <label class="block text-base font-bold mb-3 text-blue-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">edit</span>
                    Nama Gejala Baru <span class="text-red-500">*</span>
                </label>
                <input type="text" name="gejala_baru[${newIndex}][nama]" required 
                       class="w-full px-4 py-4 border border-blue-300 rounded-lg text-lg font-semibold bg-white" 
                       placeholder="Contoh: Bulu rontok berlebihan, Mata berair, Nafsu makan menurun">
            </div>
            
            <div>
                <label class="block text-sm font-bold mb-2">Deskripsi Gejala (Opsional)</label>
                <textarea name="gejala_baru[${newIndex}][deskripsi]" rows="2" 
                          class="w-full px-4 py-3 border border-slate-300 rounded-lg" 
                          placeholder="Jelaskan detail gejala ini..."></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    gejalaCount++;
    nextKodeGejala++;
    updateGejalaNumbers();
}

function hapusGejala(button) {
    const gejalaItems = document.querySelectorAll('.gejala-item');
    if (gejalaItems.length > 1) {
        button.closest('.gejala-item').remove();
        updateGejalaNumbers();
    } else {
        openMinimumGejalaModal(button);
    }
}

function updateGejalaNumbers() {
    const gejalaItems = document.querySelectorAll('.gejala-item');
    gejalaItems.forEach((item, index) => {
        item.querySelector('.gejala-number').textContent = `Gejala #${index + 1}`;
    });
}
</script>

<!-- Minimum Symptom Warning Modal -->
<div
    id="minimumGejalaModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="minimumGejalaModalTitle"
    aria-describedby="minimumGejalaModalDescription"
>
    <div class="bg-white rounded-lg p-8 max-w-sm w-full mx-4 shadow-xl transform transition-all">
        <div class="flex items-center justify-center mb-4">
            <div class="bg-amber-100 p-3 rounded-full">
                <span class="material-symbols-outlined text-amber-600 text-4xl" aria-hidden="true">warning</span>
            </div>
        </div>
        <h3 id="minimumGejalaModalTitle" class="text-xl font-bold mb-2 text-center text-gray-800">
            Gejala Tidak Dapat Dihapus
        </h3>
        <p id="minimumGejalaModalDescription" class="text-gray-600 mb-6 text-center">
            Data penyakit harus memiliki minimal satu gejala. Tambahkan gejala lain sebelum menghapus gejala ini.
        </p>
        <button
            id="minimumGejalaConfirmBtn"
            type="button"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition-colors"
            onclick="closeMinimumGejalaModal()"
        >Mengerti</button>
    </div>
</div>

<script>
let minimumGejalaTrigger = null;

function openMinimumGejalaModal(trigger) {
    const modal = document.getElementById('minimumGejalaModal');
    minimumGejalaTrigger = trigger || document.activeElement;
    modal.classList.remove('hidden');
    document.getElementById('minimumGejalaConfirmBtn').focus();
}

function closeMinimumGejalaModal() {
    document.getElementById('minimumGejalaModal').classList.add('hidden');
    if (minimumGejalaTrigger && typeof minimumGejalaTrigger.focus === 'function') {
        minimumGejalaTrigger.focus();
    }
}

document.addEventListener('click', function(event) {
    const minimumGejalaModal = document.getElementById('minimumGejalaModal');

    if (event.target === minimumGejalaModal) {
        closeMinimumGejalaModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') {
        return;
    }

    const minimumGejalaModal = document.getElementById('minimumGejalaModal');
    if (!minimumGejalaModal.classList.contains('hidden')) {
        closeMinimumGejalaModal();
    }
});
</script>

<?php $logout_url = '../logout.php'; include __DIR__ . '/../includes/logout-confirmation.php'; ?>

</body>
</html>
