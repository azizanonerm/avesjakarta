<?php
/**
 * Navigasi samping bersama untuk seluruh halaman administrator.
 */

$admin_sidebar_page = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
$admin_sidebar_admin_prefix = isset($admin_sidebar_admin_prefix)
    ? (string) $admin_sidebar_admin_prefix
    : '';
$admin_sidebar_public_prefix = isset($admin_sidebar_public_prefix)
    ? (string) $admin_sidebar_public_prefix
    : '../';
$admin_sidebar_active = match ($admin_sidebar_page) {
    'profile.php' => 'profile',
    'penyakit.php' => 'penyakit',
    'tambah_penyakit.php' => 'tambah-penyakit',
    'gejala.php' => 'gejala',
    'aturan.php' => 'aturan',
    'riwayat_diagnosis.php', 'detail-riwayat.php' => 'riwayat',
    'laporan.php' => 'laporan',
    default => 'dashboard',
};

$admin_sidebar_name = trim((string) ($_SESSION['user_name'] ?? 'Admin'));
$admin_sidebar_name = $admin_sidebar_name !== '' ? $admin_sidebar_name : 'Admin';
$admin_sidebar_initial = strtoupper(substr($admin_sidebar_name, 0, 1));

function adminSidebarLinkClass(string $menu, string $activeMenu): string
{
    return 'admin-sidebar-link' . ($menu === $activeMenu ? ' is-active' : '');
}
?>

<button
    type="button"
    class="admin-sidebar-toggle"
    aria-label="Buka navigasi administrator"
    aria-controls="sidebar"
    aria-expanded="false"
>
    <span class="material-symbols-outlined" aria-hidden="true">menu</span>
</button>

<div class="admin-sidebar-overlay" aria-hidden="true"></div>

<aside class="admin-sidebar" id="sidebar">
    <div class="admin-sidebar-brand">
        <img src="<?php echo htmlspecialchars($admin_sidebar_public_prefix); ?>assets/branding/aves-blue.png" alt="AVES - Avian &amp; Exotic Animal Veterinary Services Jakarta" class="admin-sidebar-brand-logo">
    </div>

    <nav class="admin-sidebar-nav" aria-label="Navigasi administrator">
        <ul class="admin-sidebar-menu">
            <li>
                <a
                    class="<?php echo adminSidebarLinkClass('dashboard', $admin_sidebar_active); ?>"
                    href="<?php echo htmlspecialchars($admin_sidebar_admin_prefix); ?>dashboard.php"
                    <?php echo $admin_sidebar_active === 'dashboard' ? 'aria-current="page"' : ''; ?>
                >
                    <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a
                    class="<?php echo adminSidebarLinkClass('penyakit', $admin_sidebar_active); ?>"
                    href="<?php echo htmlspecialchars($admin_sidebar_admin_prefix); ?>penyakit.php"
                    <?php echo $admin_sidebar_active === 'penyakit' ? 'aria-current="page"' : ''; ?>
                >
                    <span class="material-symbols-outlined" aria-hidden="true">coronavirus</span>
                    <span>Manajemen Penyakit</span>
                </a>
            </li>
            <li>
                <a
                    class="<?php echo adminSidebarLinkClass('tambah-penyakit', $admin_sidebar_active); ?>"
                    href="<?php echo htmlspecialchars($admin_sidebar_admin_prefix); ?>tambah_penyakit.php"
                    <?php echo $admin_sidebar_active === 'tambah-penyakit' ? 'aria-current="page"' : ''; ?>
                >
                    <span class="material-symbols-outlined" aria-hidden="true">add_circle</span>
                    <span>Tambah Penyakit Baru</span>
                </a>
            </li>
            <li>
                <a
                    class="<?php echo adminSidebarLinkClass('gejala', $admin_sidebar_active); ?>"
                    href="<?php echo htmlspecialchars($admin_sidebar_admin_prefix); ?>gejala.php"
                    <?php echo $admin_sidebar_active === 'gejala' ? 'aria-current="page"' : ''; ?>
                >
                    <span class="material-symbols-outlined" aria-hidden="true">checklist</span>
                    <span>Manajemen Gejala</span>
                </a>
            </li>
            <li>
                <a
                    class="<?php echo adminSidebarLinkClass('aturan', $admin_sidebar_active); ?>"
                    href="<?php echo htmlspecialchars($admin_sidebar_admin_prefix); ?>aturan.php"
                    <?php echo $admin_sidebar_active === 'aturan' ? 'aria-current="page"' : ''; ?>
                >
                    <span class="material-symbols-outlined" aria-hidden="true">rule</span>
                    <span>Manajemen Aturan (CF)</span>
                </a>
            </li>
            <li>
                <a
                    class="<?php echo adminSidebarLinkClass('riwayat', $admin_sidebar_active); ?>"
                    href="<?php echo htmlspecialchars($admin_sidebar_admin_prefix); ?>riwayat_diagnosis.php"
                    <?php echo $admin_sidebar_active === 'riwayat' ? 'aria-current="page"' : ''; ?>
                >
                    <span class="material-symbols-outlined" aria-hidden="true">history</span>
                    <span>Riwayat Diagnosis</span>
                </a>
            </li>
            <li class="admin-sidebar-report">
                <a
                    class="<?php echo adminSidebarLinkClass('laporan', $admin_sidebar_active); ?>"
                    href="<?php echo htmlspecialchars($admin_sidebar_admin_prefix); ?>laporan.php"
                    <?php echo $admin_sidebar_active === 'laporan' ? 'aria-current="page"' : ''; ?>
                >
                    <span class="material-symbols-outlined" aria-hidden="true">summarize</span>
                    <span>Laporan</span>
                </a>
            </li>
            <li>
                <a class="admin-sidebar-link" href="<?php echo htmlspecialchars($admin_sidebar_public_prefix); ?>index.html#cara-kerja">
                    <span class="material-symbols-outlined" aria-hidden="true">menu_book</span>
                    <span>Tata Cara</span>
                </a>
            </li>
            <li>
                <a class="admin-sidebar-link" href="<?php echo htmlspecialchars($admin_sidebar_public_prefix); ?>index.html#tentang">
                    <span class="material-symbols-outlined" aria-hidden="true">info</span>
                    <span>Tentang</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="admin-sidebar-footer">
        <a
            class="admin-sidebar-profile<?php echo $admin_sidebar_active === 'profile' ? ' is-active' : ''; ?>"
            href="<?php echo htmlspecialchars($admin_sidebar_public_prefix); ?>profile.php"
            title="Buka profil akun"
            <?php echo $admin_sidebar_active === 'profile' ? 'aria-current="page"' : ''; ?>
        >
            <span class="admin-sidebar-profile__avatar" aria-hidden="true">
                <?php echo htmlspecialchars($admin_sidebar_initial); ?>
            </span>
            <span class="admin-sidebar-profile__copy">
                <strong><?php echo htmlspecialchars($admin_sidebar_name); ?></strong>
                <small>Profil Akun</small>
            </span>
            <span class="material-symbols-outlined admin-sidebar-profile__arrow" aria-hidden="true">chevron_right</span>
        </a>
    </div>
</aside>

<script>
(function () {
    const body = document.body;
    const toggle = document.querySelector('.admin-sidebar-toggle');
    const overlay = document.querySelector('.admin-sidebar-overlay');

    if (!toggle || !overlay) {
        return;
    }

    function setSidebar(open) {
        body.classList.toggle('admin-sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Tutup navigasi administrator' : 'Buka navigasi administrator');
    }

    toggle.addEventListener('click', function () {
        setSidebar(!body.classList.contains('admin-sidebar-open'));
    });

    overlay.addEventListener('click', function () {
        setSidebar(false);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setSidebar(false);
        }
    });
})();
</script>
