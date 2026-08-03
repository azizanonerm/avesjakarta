<?php
/**
 * Register Page
 * AVES Jakarta Expert System
 */

require_once __DIR__ . '/config/security.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    header(
        'Location: '
        . (($_SESSION['user_role'] ?? '') === 'admin'
            ? 'admin/dashboard.php'
            : 'konsultasi.php')
    );
    exit;
}

require_once 'config/database.php';

$error = is_string($_SESSION['register_error'] ?? null)
    ? $_SESSION['register_error']
    : '';
$success = '';
$oldInput = is_array($_SESSION['register_old_input'] ?? null)
    ? $_SESSION['register_old_input']
    : [];
unset($_SESSION['register_error'], $_SESSION['register_old_input']);

// Handle register form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();
    $nama = limitedText($_POST['nama'] ?? '', 100);
    $email = normalizeEmail(limitedText($_POST['email'] ?? '', 190));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $password_confirm = is_string($_POST['password_confirm'] ?? null)
        ? $_POST['password_confirm']
        : '';
    
    // Validasi input
    if (empty($nama) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Semua field harus diisi';
    } elseif (!hasStrongEnoughPassword($password)) {
        $error = 'Password minimal 10 karakter dan harus mengandung huruf serta angka';
    } elseif ($password !== $password_confirm) {
        $error = 'Password tidak cocok';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else {
        // Cek apakah email sudah terdaftar
        $existing_user = fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        
        if ($existing_user) {
            $error = 'Email sudah terdaftar';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru
            try {
                execute(
                    'INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)',
                    [$nama, $email, $hashed_password, 'user']
                );
                
                $_SESSION['login_success'] = 'Registrasi berhasil! Silakan login dengan akun Anda.';
                header('Location: login.php', true, 303);
                exit;
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan saat registrasi. Silakan coba lagi.';
            }
        }
    }

    $_SESSION['register_error'] = $error;
    $_SESSION['register_old_input'] = [
        'nama' => $nama,
        'email' => $email,
    ];
    header('Location: register.php', true, 303);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/includes/pwa-head.php'; renderAvesPwaHead(); ?>
    <title>Register - AVES</title>
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
        <!-- Register Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-sm">
            <h1 class="font-headline-md text-headline-md text-on-surface mb-2">Daftar</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mb-6">Buat akun baru untuk memulai konsultasi</p>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="mb-4 p-4 bg-secondary-container text-on-secondary-container rounded-lg flex items-start gap-3">
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
            
            <!-- Register Form -->
            <form action="register.php" method="POST" class="space-y-4">
                <?php echo csrfField(); ?>
                <!-- Nama Input -->
                <div>
                    <label for="nama" class="block font-label-caps text-label-caps text-on-surface-variant mb-2">Nama Lengkap</label>
                    <input 
                        type="text" 
                        id="nama" 
                        name="nama" 
                        required
                        value="<?php echo htmlspecialchars((string) ($oldInput['nama'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full px-4 py-3 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface bg-surface-container-lowest focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-colors"
                        placeholder="Nama Anda"
                    >
                </div>
                
                <!-- Email Input -->
                <div>
                    <label for="email" class="block font-label-caps text-label-caps text-on-surface-variant mb-2">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        value="<?php echo htmlspecialchars((string) ($oldInput['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full px-4 py-3 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface bg-surface-container-lowest focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-colors"
                        placeholder="nama@email.com"
                    >
                </div>
                
                <!-- Password Input -->
                <div>
                    <label for="password" class="block font-label-caps text-label-caps text-on-surface-variant mb-2">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface bg-surface-container-lowest focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-colors"
                        placeholder="••••••••"
                    >
                    <p class="font-label-caps text-label-caps text-on-surface-variant mt-1">Minimal 10 karakter, mengandung huruf dan angka</p>
                </div>
                
                <!-- Confirm Password Input -->
                <div>
                    <label for="password_confirm" class="block font-label-caps text-label-caps text-on-surface-variant mb-2">Konfirmasi Password</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        required
                        class="w-full px-4 py-3 border border-outline-variant rounded-lg font-body-md text-body-md text-on-surface bg-surface-container-lowest focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-colors"
                        placeholder="••••••••"
                    >
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-secondary text-on-secondary font-button text-button py-3 rounded-lg hover:bg-[#004bb5] transition-colors shadow-sm mt-6"
                >
                    Daftar
                </button>
            </form>
            
            <!-- Divider -->
            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-outline-variant"></div>
                <span class="font-label-caps text-label-caps text-on-surface-variant">atau</span>
                <div class="flex-1 h-px bg-outline-variant"></div>
            </div>
            
            <!-- Login Link -->
            <p class="text-center font-body-md text-body-md text-on-surface-variant">
                Sudah punya akun? 
                <a href="login.php" class="text-secondary font-semibold hover:underline">Masuk di sini</a>
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

<?php $back_url = 'login.php'; $back_label = 'Login'; include __DIR__ . '/includes/back-button.php'; ?>
</body>
</html>
