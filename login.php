<?php
/**
 * Login Page
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/config/security.php';

// Jika sudah login, redirect ke halaman sesuai role
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: konsultasi.php');
    }
    exit;
}

require_once 'config/database.php';

$error = '';
$success = is_string($_SESSION['login_success'] ?? null)
    ? $_SESSION['login_success']
    : '';
unset($_SESSION['login_success']);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();
    $email = normalizeEmail(limitedText($_POST['email'] ?? '', 190));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    
    // Validasi input
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) > 4096) {
        $error = 'Email atau password salah';
    } elseif (isLoginTemporarilyBlocked($email)) {
        $error = 'Terlalu banyak percobaan login. Tunggu 5 menit lalu coba kembali.';
    } else {
        // Cari user berdasarkan email
        $user = fetchOne(
            'SELECT id, nama, email, password, role FROM users WHERE email = ? LIMIT 1',
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            clearFailedLogins($email);
            establishAuthenticatedSession($user);

            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                execute(
                    'UPDATE users SET password = ? WHERE id = ?',
                    [password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]
                );
            }

            // Kredensial demo lama tidak boleh dipakai untuk mengakses admin.
            $_SESSION['admin_password_warning'] =
                $user['role'] === 'admin'
                && hash_equals(
                    '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9',
                    hash('sha256', $password)
                );
            unset($_SESSION['must_change_password']);
            
            // Redirect sesuai role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: konsultasi.php');
            }
            exit;
        } else {
            recordFailedLogin($email);
            $error = 'Email atau password salah';
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title>Login - AVES</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="assets/user-account.css?v=20260731-4" rel="stylesheet">
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "tertiary-container": "#203039",
                        "on-background": "#0b1c30",
                        "primary-fixed-dim": "#adc7fd",
                        "secondary": "#0058be",
                        "on-surface-variant": "#44474f",
                        "on-secondary": "#ffffff",
                        "tertiary": "#0a1b24",
                        "on-surface": "#0b1c30",
                        "on-secondary-fixed-variant": "#004395",
                        "outline": "#747780",
                        "on-tertiary": "#ffffff",
                        "background": "#f8f9ff",
                        "on-primary-container": "#7c96c9",
                        "tertiary-fixed": "#d3e5f1",
                        "error": "#ba1a1a",
                        "surface-container-high": "#dce9ff",
                        "surface-container": "#e5eeff",
                        "on-primary-fixed-variant": "#2c4675",
                        "surface-variant": "#d3e4fe",
                        "surface-tint": "#455e8e",
                        "on-primary-fixed": "#001a40",
                        "on-secondary-fixed": "#001a42",
                        "on-primary": "#ffffff",
                        "on-secondary-container": "#fefcff",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#c4c6d0",
                        "primary-fixed": "#d7e2ff",
                        "secondary-fixed": "#d8e2ff",
                        "surface": "#f8f9ff",
                        "on-tertiary-fixed": "#0c1e26",
                        "secondary-container": "#2170e4",
                        "inverse-on-surface": "#eaf1ff",
                        "on-error": "#ffffff",
                        "error-container": "#ffdad6",
                        "primary": "#00183c",
                        "on-tertiary-container": "#8798a3",
                        "primary-container": "#0f2d5a",
                        "inverse-surface": "#213145",
                        "surface-bright": "#f8f9ff",
                        "on-tertiary-fixed-variant": "#384953",
                        "inverse-primary": "#adc7fd",
                        "surface-container-low": "#eff4ff",
                        "surface-dim": "#cbdbf5",
                        "surface-container-highest": "#d3e4fe",
                        "secondary-fixed-dim": "#adc6ff",
                        "on-error-container": "#93000a",
                        "tertiary-fixed-dim": "#b7c9d5"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "container-margin": "16px",
                        "lg": "24px",
                        "sm": "8px",
                        "xs": "4px",
                        "base": "4px",
                        "gutter": "12px",
                        "xl": "32px",
                        "md": "16px"
                    },
                    "fontFamily": {
                        "headline-md": ["Inter"],
                        "body-md": ["Inter"],
                        "label-caps": ["Inter"]
                    },
                    "fontSize": {
                        "headline-md": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}]
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen flex flex-col font-sans antialiased overflow-x-hidden relative">

<!-- TOP BANNER -->
<header class="user-topbar">
    <div class="user-topbar__inner user-topbar__inner--auth">
        <div class="user-brand-cluster" data-aves-back-host data-aves-back-theme="dark">
            <a href="index.html" class="user-brand" aria-label="Ke Beranda AVES">
                <img src="assets/branding/aves-blue.png" alt="AVES - Avian &amp; Exotic Animal Veterinary Services Jakarta" class="user-brand-logo">
            </a>
        </div>
    </div>
</header>

<!-- Main Content -->
<main class="flex-grow flex items-center justify-center px-4 py-8 relative">
    <div class="w-full max-w-md">
        <!-- Login Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-sm">
            <h1 class="font-headline-md text-headline-md text-on-surface mb-2">Masuk</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mb-6">Masukkan kredensial Anda untuk melanjutkan</p>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="mb-4 p-4 bg-surface-container text-secondary rounded-lg flex items-start gap-3">
                    <span class="material-symbols-outlined text-sm mt-0.5">check_circle</span>
                    <span class="font-body-md text-body-md"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="mb-4 p-4 bg-error-container text-on-error-container rounded-lg flex items-start gap-3">
                    <span class="material-symbols-outlined text-sm mt-0.5">error</span>
                    <span class="font-body-md text-body-md"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form action="login.php" method="POST" class="space-y-4">
                <?php echo csrfField(); ?>
                <!-- Email Input -->
                <div>
                    <label for="email" class="block font-label-caps text-label-caps text-on-surface-variant mb-2">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        class="w-full px-4 py-3 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface bg-surface-container-lowest focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-colors"
                        placeholder="nama@email.com"
                    >
                </div>
                
                <!-- Password Input -->
                <div>
                    <label for="password" class="block font-label-caps text-label-caps text-on-surface-variant mb-2">Password</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full pl-4 pr-14 py-3 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface bg-surface-container-lowest focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-colors"
                            placeholder="••••••••"
                        >
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-lg text-on-surface-variant transition-colors hover:text-secondary focus:outline-none focus:ring-2 focus:ring-inset focus:ring-secondary"
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                        >
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-secondary text-on-secondary font-button text-button py-3 rounded-lg hover:bg-[#004bb5] transition-colors shadow-sm mt-6"
                >
                    Masuk
                </button>
            </form>
            
            <!-- Divider -->
            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-outline-variant"></div>
                <span class="font-label-caps text-label-caps text-on-surface-variant">atau</span>
                <div class="flex-1 h-px bg-outline-variant"></div>
            </div>
            
            <!-- Register Link -->
            <p class="text-center font-body-md text-body-md text-on-surface-variant">
                Belum punya akun? 
                <a href="register.php" class="text-secondary font-semibold hover:underline">Daftar di sini</a>
            </p>
            
        </div>
        
    </div>
</main>

<!-- Footer -->
<footer class="bg-primary w-full mt-auto">
    <div class="flex justify-center items-center px-margin-desktop py-6 w-full max-w-container-max mx-auto text-sm">
        <span class="text-on-primary opacity-70">© 2026 AVES — Avian &amp; Exotic Animal Veterinary Services Jakarta</span>
    </div>
</footer>

<?php $back_url = 'index.html'; $back_label = 'Beranda'; include __DIR__ . '/includes/back-button.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const toggleIcon = togglePassword ? togglePassword.querySelector('.material-symbols-outlined') : null;

    if (!passwordInput || !togglePassword || !toggleIcon) {
        return;
    }

    togglePassword.addEventListener('click', function () {
        const willShowPassword = passwordInput.type === 'password';

        passwordInput.type = willShowPassword ? 'text' : 'password';
        toggleIcon.textContent = willShowPassword ? 'visibility_off' : 'visibility';
        togglePassword.setAttribute('aria-label', willShowPassword ? 'Sembunyikan password' : 'Tampilkan password');
        togglePassword.setAttribute('aria-pressed', String(willShowPassword));
        passwordInput.focus();
    });
});
</script>
</body>
</html>
