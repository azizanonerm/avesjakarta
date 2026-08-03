<?php
/**
 * Admin - Riwayat Diagnosis
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/../config/security.php';

// Cek login dan role admin
requireAdminUser('../login.php');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_history') {
    verifyCsrfOrAbort();

    $historyId = filter_input(INPUT_POST, 'id_riwayat', FILTER_VALIDATE_INT) ?: 0;
    $returnPage = max(1, filter_input(INPUT_POST, 'return_page', FILTER_VALIDATE_INT) ?: 1);
    $returnSearch = limitedText($_POST['return_search'] ?? '', 100);

    if ($historyId <= 0) {
        $_SESSION['history_flash'] = [
            'type' => 'error',
            'message' => 'Data riwayat yang dipilih tidak valid.',
        ];
    } else {
        $deleted = execute('DELETE FROM riwayat WHERE id_riwayat = ?', [$historyId]);
        $_SESSION['history_flash'] = [
            'type' => $deleted > 0 ? 'success' : 'error',
            'message' => $deleted > 0
                ? 'Riwayat diagnosis berhasil dihapus.'
                : 'Riwayat diagnosis tidak ditemukan atau sudah dihapus.',
        ];
    }

    $redirectQuery = ['page' => $returnPage];
    if ($returnSearch !== '') {
        $redirectQuery['search'] = $returnSearch;
    }

    header('Location: riwayat_diagnosis.php?' . http_build_query($redirectQuery));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_history_bulk') {
    verifyCsrfOrAbort();

    $submittedIds = $_POST['history_ids'] ?? [];
    $historyIds = is_array($submittedIds)
        ? array_values(array_unique(array_filter(
            array_map('intval', $submittedIds),
            static fn (int $id): bool => $id > 0
        )))
        : [];
    $historyIds = array_slice($historyIds, 0, 100);
    $returnPage = max(1, filter_input(INPUT_POST, 'return_page', FILTER_VALIDATE_INT) ?: 1);
    $returnSearch = limitedText($_POST['return_search'] ?? '', 100);

    if (empty($historyIds)) {
        $_SESSION['history_flash'] = [
            'type' => 'error',
            'message' => 'Pilih minimal satu riwayat diagnosis yang ingin dihapus.',
        ];
    } else {
        $placeholders = implode(',', array_fill(0, count($historyIds), '?'));
        $deleted = execute(
            'DELETE FROM riwayat WHERE id_riwayat IN (' . $placeholders . ')',
            $historyIds
        );
        $_SESSION['history_flash'] = [
            'type' => $deleted > 0 ? 'success' : 'error',
            'message' => $deleted > 0
                ? $deleted . ' riwayat diagnosis berhasil dihapus.'
                : 'Riwayat diagnosis tidak ditemukan atau sudah dihapus.',
        ];
    }

    $redirectQuery = ['page' => $returnPage];
    if ($returnSearch !== '') {
        $redirectQuery['search'] = $returnSearch;
    }

    header('Location: riwayat_diagnosis.php?' . http_build_query($redirectQuery));
    exit;
}

$historyFlash = $_SESSION['history_flash'] ?? null;
unset($_SESSION['history_flash']);

$search = limitedText($_GET['search'] ?? '', 100);
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get statistik
$total_users = fetchOne('SELECT COUNT(*) as count FROM users WHERE role = "user"')['count'];
$total_diagnosa = fetchOne('SELECT COUNT(*) as count FROM riwayat')['count'];
$avg_cf = fetchOne('SELECT AVG(nilai_cf) as avg FROM riwayat')['avg'];
$avg_cf = round($avg_cf * 100, 1);

// Get riwayat global
if ($search) {
    $riwayat_list = fetchAll(
        'SELECT r.*, u.nama FROM riwayat r JOIN users u ON r.id_user = u.id WHERE u.nama LIKE ? OR r.penyakit_tertinggi LIKE ? ORDER BY r.tanggal DESC LIMIT ? OFFSET ?',
        ['%' . $search . '%', '%' . $search . '%', $limit, $offset]
    );
    $total = fetchOne('SELECT COUNT(*) as count FROM riwayat r JOIN users u ON r.id_user = u.id WHERE u.nama LIKE ? OR r.penyakit_tertinggi LIKE ?', ['%' . $search . '%', '%' . $search . '%'])['count'];
} else {
    $riwayat_list = fetchAll(
        'SELECT r.*, u.nama FROM riwayat r JOIN users u ON r.id_user = u.id ORDER BY r.tanggal DESC LIMIT ? OFFSET ?',
        [$limit, $offset]
    );
    $total = fetchOne('SELECT COUNT(*) as count FROM riwayat')['count'];
}

$total_pages = ceil($total / $limit);

$page_title = 'Riwayat Diagnosis';
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
    <style>
        .history-table-shell {
            width: 100%;
            overflow: hidden;
        }

        #bulkDeleteButton[hidden] {
            display: none !important;
        }

        .history-table {
            width: 100%;
            table-layout: fixed;
        }

        .history-table th,
        .history-table td {
            min-width: 0;
        }

        .history-table__date time {
            display: block;
            line-height: 1.35;
        }

        .history-table__date time span {
            display: block;
        }

        .history-table__actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .history-table__action {
            min-width: 0;
            padding: 8px 10px;
        }

        @media (max-width: 1200px) {
            .history-table th,
            .history-table td {
                padding-right: 8px !important;
                padding-left: 8px !important;
            }

            .history-table__avatar {
                display: none;
            }

            .history-table__action {
                width: 40px;
                height: 40px;
                padding: 0;
            }

            .history-table__action-label {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
            }
        }

        @media (max-width: 700px) {
            .history-table-shell {
                overflow: visible;
                background: transparent !important;
                box-shadow: none !important;
            }

            .history-table,
            .history-table tbody {
                display: block;
            }

            .history-table colgroup,
            .history-table thead {
                display: none;
            }

            .history-table tr {
                display: grid;
                height: auto !important;
                grid-template-columns: 34px minmax(0, 1fr) auto;
                gap: 8px 10px;
                margin-bottom: 12px;
                padding: 14px;
                border: 1px solid #dbe5f1;
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 3px 12px rgba(15, 45, 90, .06);
            }

            .history-table td {
                padding: 0 !important;
                border: 0;
            }

            .history-table__select {
                grid-column: 1;
                grid-row: 1;
            }

            .history-table__number {
                display: none !important;
            }

            .history-table__user {
                grid-column: 2;
                grid-row: 1;
            }

            .history-table__date {
                grid-column: 2 / -1;
                grid-row: 2;
            }

            .history-table__disease {
                grid-column: 2 / -1;
                grid-row: 3;
            }

            .history-table__cf {
                grid-column: 3;
                grid-row: 1;
            }

            .history-table__actions-cell {
                grid-column: 1 / -1;
                grid-row: 4;
                padding-top: 4px !important;
            }

            .history-table__actions {
                justify-content: flex-end;
            }

            .history-table__action {
                width: auto;
                min-width: 94px;
                padding: 8px 12px;
            }

            .history-table__action-label {
                position: static;
                width: auto;
                height: auto;
                overflow: visible;
                clip: auto;
                white-space: normal;
            }
        }
    </style>
</head>
<body class="admin-shell admin-sidebar-shell bg-background text-on-surface font-body-md">

<?php require __DIR__ . '/includes/sidebar.php'; ?>

<!-- Header -->
<header class="bg-green-600 text-white sticky top-0 shadow-sm z-40">
    <div class="flex justify-between items-center px-6 py-4 max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-white">Riwayat Diagnosis</h1>
        </div>
        <div class="admin-header-actions">
            <a href="../logout.php" onclick="confirmLogout('../logout.php', event)" class="admin-header-logout">Keluar</a>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-8">

    <!-- Breadcrumb -->
    <nav class="flex items-center text-sm mb-6 gap-2">
        <a href="dashboard.php" class="text-secondary hover:underline flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">home</span>
            Dashboard
        </a>
        <span class="mx-2 text-gray-400">/</span>
        <span class="text-primary font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">history</span>
            Riwayat Diagnosis
        </span>
    </nav>

    <!-- Title Section -->
    <section class="mb-8">
        <h2 class="text-3xl font-bold mb-2">Riwayat Diagnosis</h2>
        <p class="text-gray-600">Pantau semua aktivitas diagnosa pengguna dalam sistem</p>
    </section>

    <?php if (is_array($historyFlash)): ?>
        <div
            class="mb-6 rounded-lg border px-4 py-3 font-semibold <?php echo ($historyFlash['type'] ?? '') === 'success'
                ? 'border-green-300 bg-green-50 text-green-800'
                : 'border-red-300 bg-red-50 text-red-800'; ?>"
            role="status"
        >
            <?php echo htmlspecialchars((string) ($historyFlash['message'] ?? '')); ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <div class="flex items-center gap-4">
                <div class="bg-blue-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-blue-600">group</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">TOTAL USER</p>
                    <p class="text-3xl font-bold"><?php echo number_format($total_users); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
            <div class="flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-green-600">assessment</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">TOTAL DIAGNOSA</p>
                    <p class="text-3xl font-bold"><?php echo number_format($total_diagnosa); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
            <div class="flex items-center gap-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <span class="material-symbols-outlined text-purple-600">trending_up</span>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">AKURASI RATA-RATA</p>
                    <p class="text-3xl font-bold"><?php echo $avg_cf; ?>%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pencarian -->
    <div class="mb-6">
        <form method="GET" class="w-full">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari berdasarkan nama atau penyakit..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary">
        </form>
    </div>

    <!-- Pilih dan hapus banyak riwayat -->
    <form method="POST" id="bulkDeleteForm">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="delete_history_bulk">
        <input type="hidden" name="return_page" value="<?php echo (int) $page; ?>">
        <input type="hidden" name="return_search" value="<?php echo htmlspecialchars($search, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

        <div class="mb-3 flex flex-col gap-3 rounded-lg border border-blue-100 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-4">
                <label class="inline-flex cursor-pointer items-center gap-2 font-semibold text-gray-700">
                    <input
                        type="checkbox"
                        id="selectAllHistory"
                        class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        onchange="toggleAllHistory(this.checked)"
                    >
                    <span>Pilih semua di halaman ini</span>
                </label>
                <span id="selectedHistoryCount" class="text-sm font-semibold text-gray-500">0 dipilih</span>
            </div>
            <button
                type="button"
                id="bulkDeleteButton"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                onclick="openBulkDeleteHistoryModal()"
                hidden
                disabled
            >
                <span class="material-symbols-outlined text-lg">delete</span>
                Hapus Terpilih
            </button>
        </div>

        <!-- Table -->
        <div id="riwayat-table" class="history-table-shell bg-white rounded-lg shadow scroll-mt-36">
        <table class="history-table">
            <colgroup>
                <col style="width: 4%">
                <col style="width: 5%">
                <col style="width: 15%">
                <col style="width: 18%">
                <col style="width: 28%">
                <col style="width: 9%">
                <col style="width: 21%">
            </colgroup>
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="no-print px-3 py-3 text-center text-sm font-semibold text-gray-700">
                        <span class="sr-only">Pilih</span>
                    </th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">NO</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">NAMA USER</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">TANGGAL</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">PENYAKIT</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">CF</th>
                    <th class="no-print px-6 py-3 text-center text-sm font-semibold text-gray-700">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($riwayat_list)): ?>
                    <?php $no = $offset + 1; foreach ($riwayat_list as $riwayat): 
                        $tanggal = new DateTime($riwayat['tanggal']);
                    ?>
                        <tr class="h-[88px] border-b hover:bg-gray-50">
                            <td class="history-table__select no-print px-3 py-4 text-center align-middle">
                                <input
                                    type="checkbox"
                                    name="history_ids[]"
                                    value="<?php echo (int) $riwayat['id_riwayat']; ?>"
                                    class="history-checkbox h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    aria-label="<?php echo htmlspecialchars(
                                        'Pilih riwayat ' . $riwayat['penyakit_tertinggi'] . ' milik ' . $riwayat['nama'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ); ?>"
                                    onchange="updateBulkHistorySelection()"
                                >
                            </td>
                            <td class="history-table__number px-4 py-4 align-middle text-gray-600 whitespace-nowrap"><?php echo $no++; ?></td>
                            <td class="history-table__user px-4 py-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="history-table__avatar w-10 h-10 shrink-0 bg-blue-100 rounded-full flex items-center justify-center font-bold text-blue-600">
                                        <?php echo strtoupper(substr($riwayat['nama'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold truncate"><?php echo htmlspecialchars($riwayat['nama']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="history-table__date px-4 py-4 align-middle text-gray-600 text-sm">
                                <time datetime="<?php echo htmlspecialchars($tanggal->format(DateTimeInterface::ATOM)); ?>">
                                    <span><?php echo $tanggal->format('d M Y'); ?></span>
                                    <span><?php echo $tanggal->format('H:i'); ?> WIB</span>
                                </time>
                            </td>
                            <td class="history-table__disease px-4 py-4 align-middle font-semibold leading-snug break-words"><?php echo htmlspecialchars($riwayat['penyakit_tertinggi']); ?></td>
                            <td class="history-table__cf px-3 py-4 align-middle text-center whitespace-nowrap">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                    <?php echo round($riwayat['nilai_cf'] * 100, 1); ?>%
                                </span>
                            </td>
                            <td class="history-table__actions-cell no-print px-3 py-4 align-middle">
                                <div class="history-table__actions">
                                    <a
                                        href="detail-riwayat.php?<?php
                                            echo htmlspecialchars(
                                                http_build_query([
                                                    'id' => (int) $riwayat['id_riwayat'],
                                                    'return_page' => $page,
                                                    'return_search' => $search,
                                                ]),
                                                ENT_QUOTES | ENT_SUBSTITUTE,
                                                'UTF-8'
                                            );
                                        ?>"
                                        class="history-table__action inline-flex items-center justify-center gap-1 rounded-lg border border-blue-200 bg-blue-50 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                                        title="Lihat detail diagnosis"
                                    >
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                        <span class="history-table__action-label">Detail</span>
                                    </a>
                                    <button
                                        type="button"
                                        class="history-table__action inline-flex items-center justify-center gap-1 rounded-lg border border-red-200 bg-red-50 text-sm font-semibold text-red-700 hover:bg-red-100"
                                        title="Hapus riwayat diagnosis"
                                        data-history-id="<?php echo (int) $riwayat['id_riwayat']; ?>"
                                        data-history-name="<?php echo htmlspecialchars($riwayat['penyakit_tertinggi'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                        onclick="openDeleteHistoryModal(this)"
                                    >
                                        <span class="material-symbols-outlined text-base">delete</span>
                                        <span class="history-table__action-label">Hapus</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">Tidak ada data ditemukan</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </form>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav class="flex items-center justify-center gap-2 mt-7" aria-label="Navigasi halaman riwayat">
            <?php if ($page > 1): ?>
                <a
                    href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>#riwayat-table"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                    aria-label="Halaman sebelumnya"
                >←</a>
            <?php else: ?>
                <span class="inline-flex h-11 w-11 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400" aria-disabled="true">←</span>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a
                    href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>#riwayat-table"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border font-semibold <?php echo $i === $page
                        ? 'border-blue-600 bg-secondary text-white shadow-md'
                        : 'border-gray-300 bg-white text-gray-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700'; ?>"
                    <?php echo $i === $page ? 'aria-current="page"' : ''; ?>
                >
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a
                    href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>#riwayat-table"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                    aria-label="Halaman berikutnya"
                >→</a>
            <?php else: ?>
                <span class="inline-flex h-11 w-11 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400" aria-disabled="true">→</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

</main>

<!-- Delete History Confirmation Modal -->
<div id="deleteHistoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]" role="dialog" aria-modal="true" aria-labelledby="deleteHistoryTitle">
    <form method="POST" class="bg-white rounded-lg p-8 max-w-sm w-full mx-4 shadow-xl transform transition-all">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="delete_history">
        <input type="hidden" name="id_riwayat" id="deleteHistoryId" value="">
        <input type="hidden" name="return_page" value="<?php echo (int) $page; ?>">
        <input type="hidden" name="return_search" value="<?php echo htmlspecialchars($search, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

        <div class="flex items-center justify-center mb-4">
            <div class="bg-red-100 p-3 rounded-full">
                <span class="material-symbols-outlined text-red-600 text-4xl">delete</span>
            </div>
        </div>
        <h3 id="deleteHistoryTitle" class="text-xl font-bold mb-2 text-center text-gray-800">Konfirmasi Hapus</h3>
        <p class="text-gray-600 mb-2 text-center">Apakah Anda yakin ingin menghapus riwayat diagnosis ini?</p>
        <p id="deleteHistoryName" class="font-semibold text-gray-800 mb-2 text-center"></p>
        <p class="text-sm text-red-600 mb-6 text-center">Data yang sudah dihapus tidak dapat dikembalikan.</p>

        <div class="flex gap-3">
            <button type="button" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-semibold text-gray-700 transition-colors" onclick="closeDeleteHistoryModal()">Batal</button>
            <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold text-center transition-colors">Hapus</button>
        </div>
    </form>
</div>

<!-- Bulk Delete History Confirmation Modal -->
<div id="bulkDeleteHistoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]" role="dialog" aria-modal="true" aria-labelledby="bulkDeleteHistoryTitle">
    <div class="bg-white rounded-lg p-8 max-w-sm w-full mx-4 shadow-xl transform transition-all">
        <div class="flex items-center justify-center mb-4">
            <div class="bg-red-100 p-3 rounded-full">
                <span class="material-symbols-outlined text-red-600 text-4xl">delete_sweep</span>
            </div>
        </div>
        <h3 id="bulkDeleteHistoryTitle" class="text-xl font-bold mb-2 text-center text-gray-800">Hapus Riwayat Terpilih?</h3>
        <p class="text-gray-600 mb-2 text-center">
            Anda akan menghapus <strong id="bulkDeleteHistoryCount">0 riwayat</strong>.
        </p>
        <p class="text-sm text-red-600 mb-6 text-center">Semua data yang dipilih akan dihapus permanen dan tidak dapat dikembalikan.</p>

        <div class="flex gap-3">
            <button type="button" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-semibold text-gray-700 transition-colors" onclick="closeBulkDeleteHistoryModal()">Batal</button>
            <button type="submit" form="bulkDeleteForm" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold text-center transition-colors">Hapus Semua</button>
        </div>
    </div>
</div>

<script>
function openDeleteHistoryModal(button) {
    const modal = document.getElementById('deleteHistoryModal');
    document.getElementById('deleteHistoryId').value = button.dataset.historyId || '';
    document.getElementById('deleteHistoryName').textContent = button.dataset.historyName || '';
    modal.classList.remove('hidden');
    modal.querySelector('button[type="button"]').focus();
}

function closeDeleteHistoryModal() {
    document.getElementById('deleteHistoryModal').classList.add('hidden');
}

function getSelectedHistoryCheckboxes() {
    return Array.from(document.querySelectorAll('.history-checkbox:checked'));
}

function updateBulkHistorySelection() {
    const checkboxes = Array.from(document.querySelectorAll('.history-checkbox'));
    const selectedCount = getSelectedHistoryCheckboxes().length;
    const selectAll = document.getElementById('selectAllHistory');
    const deleteButton = document.getElementById('bulkDeleteButton');

    document.getElementById('selectedHistoryCount').textContent = selectedCount + ' dipilih';
    const hasSelection = selectedCount > 0;
    deleteButton.hidden = !hasSelection;
    deleteButton.disabled = !hasSelection;
    selectAll.checked = checkboxes.length > 0 && selectedCount === checkboxes.length;
    selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
}

function toggleAllHistory(checked) {
    document.querySelectorAll('.history-checkbox').forEach(function(checkbox) {
        checkbox.checked = checked;
    });
    updateBulkHistorySelection();
}

function openBulkDeleteHistoryModal() {
    const selectedCount = getSelectedHistoryCheckboxes().length;
    if (selectedCount === 0) {
        return;
    }

    document.getElementById('bulkDeleteHistoryCount').textContent =
        selectedCount + (selectedCount === 1 ? ' riwayat' : ' riwayat');
    const modal = document.getElementById('bulkDeleteHistoryModal');
    modal.classList.remove('hidden');
    modal.querySelector('button[type="button"]').focus();
}

function closeBulkDeleteHistoryModal() {
    document.getElementById('bulkDeleteHistoryModal').classList.add('hidden');
}

document.addEventListener('click', function(event) {
    const deleteHistoryModal = document.getElementById('deleteHistoryModal');
    const bulkDeleteHistoryModal = document.getElementById('bulkDeleteHistoryModal');

    if (event.target === deleteHistoryModal) {
        closeDeleteHistoryModal();
    }
    if (event.target === bulkDeleteHistoryModal) {
        closeBulkDeleteHistoryModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteHistoryModal();
        closeBulkDeleteHistoryModal();
    }
});
</script>

<?php $logout_url = '../logout.php'; include __DIR__ . '/../includes/logout-confirmation.php'; ?>

</body>
</html>
