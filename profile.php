<?php
/**
 * Profile User
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/config/security.php';

// Cek login
requireAuthenticatedUser('login.php');

require_once 'config/database.php';

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] ?? '') === 'admin';
$error = '';
$success = '';
$force_password_change = false;

// Get user info
$user = fetchOne('SELECT * FROM users WHERE id = ?', [$user_id]);

// Statistik profil disesuaikan dengan role akun.
if ($is_admin) {
    $total_diagnosa = fetchOne('SELECT COUNT(*) as count FROM riwayat')['count'];
    $avg_cf = fetchOne('SELECT AVG(nilai_cf) as avg FROM riwayat')['avg'];
} else {
    $total_diagnosa = fetchOne(
        'SELECT COUNT(*) as count FROM riwayat WHERE id_user = ?',
        [$user_id]
    )['count'];
    $avg_cf = fetchOne(
        'SELECT AVG(nilai_cf) as avg FROM riwayat WHERE id_user = ?',
        [$user_id]
    )['avg'];
}
$avg_cf = round($avg_cf * 100, 1) ?? 0;

// Handle update profile
$profile_action = limitedText($_POST['action'] ?? '', 30);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile_action === 'update_profile') {
    verifyCsrfOrAbort();
    $nama = limitedText($_POST['nama'] ?? '', 100);
    $email = normalizeEmail(limitedText($_POST['email'] ?? '', 190));
    
    if (empty($nama) || empty($email)) {
        $error = 'Nama dan email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (fetchOne('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $user_id])) {
        $error = 'Email sudah digunakan oleh akun lain';
    } else {
        try {
            execute('UPDATE users SET nama = ?, email = ? WHERE id = ?', [$nama, $email, $user_id]);
            $_SESSION['user_name'] = $nama;
            $_SESSION['user_email'] = $email;
            $user = fetchOne('SELECT * FROM users WHERE id = ?', [$user_id]);
            $success = 'Profil berhasil diperbarui';
        } catch (Exception $e) {
            error_log('Profile update failed: ' . $e->getMessage());
            $error = 'Profil belum dapat diperbarui. Silakan coba kembali.';
        }
    }
}

// Handle update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile_action === 'update_password') {
    verifyCsrfOrAbort();
    $old_password = is_string($_POST['old_password'] ?? null) ? $_POST['old_password'] : '';
    $new_password = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $confirm_password = is_string($_POST['confirm_password'] ?? null)
        ? $_POST['confirm_password']
        : '';
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua field password harus diisi';
    } elseif (!hasStrongEnoughPassword($new_password)) {
        $error = 'Password baru minimal 10 karakter dan harus mengandung huruf serta angka';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Password baru tidak cocok';
    } elseif (!password_verify($old_password, $user['password'])) {
        $error = 'Password lama tidak sesuai';
    } else {
        try {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            execute('UPDATE users SET password = ? WHERE id = ?', [$hashed, $user_id]);
            unset($_SESSION['must_change_password'], $_SESSION['admin_password_warning']);
            $success = 'Password berhasil diperbarui';
        } catch (Exception $e) {
            error_log('Password update failed: ' . $e->getMessage());
            $error = 'Password belum dapat diperbarui. Silakan coba kembali.';
        }
    }
}

$page_title = $is_admin ? 'Profil Admin' : 'Profile';
$profile_back_url = $is_admin ? 'admin/dashboard.php' : 'konsultasi.php';
$profile_back_label = $is_admin ? 'Dashboard Admin' : 'konsultasi';
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
    <?php if ($is_admin): ?>
        <link href="admin/assets/admin-theme.css?v=20260731-5" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="user-account-page user-account-page--header-back<?php echo $is_admin ? ' user-account-page--admin-profile admin-sidebar-shell' : ''; ?>">

<?php if ($is_admin): ?>
    <?php
    $admin_sidebar_admin_prefix = 'admin/';
    $admin_sidebar_public_prefix = '';
    require __DIR__ . '/admin/includes/sidebar.php';
    ?>
<?php endif; ?>

<!-- Header -->
<header class="user-topbar">
    <div class="user-topbar__inner">
        <?php if ($is_admin): ?>
            <div class="user-brand-cluster">
                <div class="user-brand">
                    <strong>Profil Admin</strong>
                </div>
            </div>
        <?php else: ?>
            <div class="user-brand-cluster">
                <a
                    href="<?php echo htmlspecialchars($profile_back_url); ?>"
                    class="user-header-back"
                    title="Kembali ke <?php echo htmlspecialchars($profile_back_label); ?>"
                >
                    <span class="user-header-back__icon" aria-hidden="true">&larr;</span>
                    <span class="user-header-back__label">Kembali</span>
                </a>
                <a href="index.html" class="user-brand" aria-label="Ke Beranda AVES">
                    <img class="user-brand-logo" src="assets/branding/aves-blue.png" alt="AVES - Avian &amp; Exotic Animal Veterinary Services Jakarta">
                </a>
            </div>
        <?php endif; ?>
        <div class="user-header-actions">
            <?php if (!$is_admin): ?>
                <span class="user-greeting">Halo, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <?php endif; ?>
            <a href="logout.php" onclick="confirmLogout('logout.php', event)" class="user-logout">
                Keluar
            </a>
        </div>
    </div>
</header>

<main class="user-account-main">

    <!-- Alert Messages -->
    <?php if ($success): ?>
        <div class="user-alert user-alert--success">
            <?php echo htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="user-alert user-alert--error">
            <?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($force_password_change): ?>
        <div class="user-alert user-alert--error">
            Password administrator bawaan harus diganti sebelum menu admin dapat digunakan.
        </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <section class="user-hero">
        <div class="user-hero__content profile-identity">
            <div class="profile-avatar">
                <?php echo htmlspecialchars(strtoupper(substr($user['nama'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="profile-identity__text">
                <span class="user-hero__eyebrow">
                    <span class="material-symbols-outlined">verified_user</span>
                    <?php echo $is_admin ? 'Akun Administrator' : 'Akun Pengguna'; ?>
                </span>
                <h2><?php echo htmlspecialchars($user['nama']); ?></h2>
                <div class="profile-identity__meta">
                    <span>
                        <span class="material-symbols-outlined text-sm">mail</span>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </span>
                    <span>
                        <span class="material-symbols-outlined text-sm">calendar_month</span>
                        Bergabung <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Grid -->
    <div class="profile-stats">
        <div class="profile-stat">
            <div class="profile-stat__icon">
                <span class="material-symbols-outlined">assessment</span>
            </div>
            <p class="profile-stat__label"><?php echo $is_admin ? 'Total Diagnosis Sistem' : 'Total Diagnosis'; ?></p>
            <p class="profile-stat__value"><?php echo $total_diagnosa; ?></p>
        </div>
        
        <div class="profile-stat">
            <div class="profile-stat__icon">
                <span class="material-symbols-outlined">trending_up</span>
            </div>
            <p class="profile-stat__label"><?php echo $is_admin ? 'Rata-rata CF Sistem' : 'Rata-rata CF'; ?></p>
            <p class="profile-stat__value"><?php echo $avg_cf; ?>%</p>
        </div>

        <div class="profile-stat">
            <div class="profile-stat__icon">
                <span class="material-symbols-outlined">shield</span>
            </div>
            <p class="profile-stat__label">Status Akun</p>
            <p class="profile-stat__value capitalize"><?php echo $user['role']; ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="profile-tabs">
        <button type="button" onclick="switchTab('info')" id="tab-info" class="profile-tab is-active">
            Informasi Profil
        </button>
        <button type="button" onclick="switchTab('password')" id="tab-password" class="profile-tab">
            Ubah Password
        </button>
    </div>

    <!-- Tab Content -->
    
    <!-- Tab 1: Informasi Profil -->
    <div id="content-info">
        <div class="profile-panel">
            <h3 class="profile-panel__title">
                <span class="material-symbols-outlined">person</span>
                Informasi Pribadi
            </h3>
            
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="profile-field">
                    <label for="profile-name">Nama Lengkap</label>
                    <input id="profile-name" type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                </div>
                
                <div class="profile-field">
                    <label for="profile-email">Email</label>
                    <input id="profile-email" type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="profile-field">
                    <label for="profile-role">Role</label>
                    <input id="profile-role" type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                </div>
                
                <div class="profile-field">
                    <label for="profile-created-at">Bergabung Sejak</label>
                    <input id="profile-created-at" type="text" value="<?php echo date('d M Y H:i', strtotime($user['created_at'])); ?>" disabled>
                </div>
                
                <button type="submit" class="user-button">
                    <span class="material-symbols-outlined">save</span>
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Tab 2: Ubah Password -->
    <div id="content-password" class="hidden">
        <div class="profile-panel">
            <h3 class="profile-panel__title">
                <span class="material-symbols-outlined">lock</span>
                Ubah Password
            </h3>
            
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_password">
                
                <div class="profile-field">
                    <label for="old-password">Password Lama</label>
                    <input id="old-password" type="password" name="old_password" required>
                </div>
                
                <div class="profile-field">
                    <label for="new-password">Password Baru</label>
                    <input id="new-password" type="password" name="new_password" required minlength="10" maxlength="4096">
                </div>
                
                <div class="profile-field">
                    <label for="confirm-password">Konfirmasi Password Baru</label>
                    <input id="confirm-password" type="password" name="confirm_password" required minlength="10" maxlength="4096">
                </div>
                
                <p class="profile-help">Password minimal 10 karakter dan harus mengandung huruf serta angka.</p>
                
                <button type="submit" class="user-button">
                    <span class="material-symbols-outlined">lock_reset</span>
                    Perbarui Password
                </button>
            </form>
        </div>
    </div>

</main>

<!-- Bottom Navigation -->
<?php if (!$is_admin): ?>
    <nav class="user-bottom-nav" aria-label="Navigasi utama pengguna">
        <a href="konsultasi.php">
            <span class="material-symbols-outlined">health_and_safety</span>
            <span>Diagnosis</span>
        </a>
        <a href="riwayat.php">
            <span class="material-symbols-outlined">history</span>
            <span>History</span>
        </a>
        <a href="profile.php" class="is-active" aria-current="page">
            <span class="material-symbols-outlined">person</span>
            <span>Profile</span>
        </a>
    </nav>
<?php endif; ?>

<script>
function switchTab(tab) {
    document.getElementById('content-info').classList.add('hidden');
    document.getElementById('content-password').classList.add('hidden');
    
    document.getElementById('tab-info').classList.remove('is-active');
    document.getElementById('tab-password').classList.remove('is-active');
    
    document.getElementById('content-' + tab).classList.remove('hidden');
    document.getElementById('tab-' + tab).classList.add('is-active');
}

<?php if ($force_password_change): ?>
switchTab('password');
<?php endif; ?>
</script>

<?php $logout_url = 'logout.php'; include __DIR__ . '/includes/logout-confirmation.php'; ?>
</body>
</html>
