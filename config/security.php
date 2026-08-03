<?php
/**
 * Lapisan keamanan bersama untuk seluruh halaman AVES Jakarta.
 */

declare(strict_types=1);

const AVES_SESSION_IDLE_TIMEOUT = 1800;
const AVES_SESSION_ABSOLUTE_TIMEOUT = 28800;
const AVES_SESSION_REGENERATE_INTERVAL = 900;
const AVES_MAX_LOGIN_ATTEMPTS = 5;
const AVES_LOGIN_LOCK_SECONDS = 300;

function isHttpsRequest(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function sendSecurityHeaders(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com data:; "
        . "img-src 'self' data:; connect-src 'self'; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
    );
}

function secureSessionStart(): void
{
    sendSecurityHeaders();

    if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('display_errors', '0');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_trans_sid', '0');

    session_name('AVESSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    $now = time();
    $lastActivity = (int) ($_SESSION['_security_last_activity'] ?? 0);
    $createdAt = (int) ($_SESSION['_security_created_at'] ?? 0);
    $userAgentHash = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    $storedUserAgentHash = (string) ($_SESSION['_security_user_agent'] ?? '');

    $expired = ($lastActivity > 0 && ($now - $lastActivity) > AVES_SESSION_IDLE_TIMEOUT)
        || ($createdAt > 0 && ($now - $createdAt) > AVES_SESSION_ABSOLUTE_TIMEOUT)
        || ($storedUserAgentHash !== '' && !hash_equals($storedUserAgentHash, $userAgentHash));

    if ($expired) {
        clearSessionData();
        session_regenerate_id(true);
    } elseif (
        isset($_SESSION['user_id'])
        && ($now - (int) ($_SESSION['_security_regenerated_at'] ?? 0)) > AVES_SESSION_REGENERATE_INTERVAL
    ) {
        session_regenerate_id(true);
        $_SESSION['_security_regenerated_at'] = $now;
    }

    $_SESSION['_security_created_at'] = (int) ($_SESSION['_security_created_at'] ?? $now);
    $_SESSION['_security_last_activity'] = $now;
    $_SESSION['_security_regenerated_at'] = (int) ($_SESSION['_security_regenerated_at'] ?? $now);
    $_SESSION['_security_user_agent'] = $userAgentHash;
}

function clearSessionData(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => $params['samesite'] ?: 'Lax',
        ]);
    }
}

function destroySecureSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        secureSessionStart();
    }

    clearSessionData();
    session_destroy();
}

function establishAuthenticatedSession(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = (string) $user['nama'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) $user['role'];
    $_SESSION['_security_created_at'] = time();
    $_SESSION['_security_last_activity'] = time();
    $_SESSION['_security_regenerated_at'] = time();
}

function requireAuthenticatedUser(string $loginUrl = 'login.php'): void
{
    if ((int) ($_SESSION['user_id'] ?? 0) <= 0) {
        header('Location: ' . $loginUrl);
        exit;
    }
}

function requireRegularUser(
    string $loginUrl = 'login.php',
    string $adminHomeUrl = 'admin/dashboard.php'
): void {
    requireAuthenticatedUser($loginUrl);

    $role = (string) ($_SESSION['user_role'] ?? '');
    if ($role === 'admin') {
        header('Location: ' . $adminHomeUrl);
        exit;
    }

    if ($role !== 'user') {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

function requireAdminUser(string $loginUrl = '../login.php'): void
{
    requireAuthenticatedUser($loginUrl);

    if ((string) ($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak.');
    }

}

function csrfToken(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="'
        . htmlspecialchars(csrfToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '">';
}

function verifyCsrfOrAbort(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    $stored = $_SESSION['_csrf_token'] ?? '';

    if (
        !is_string($submitted)
        || !is_string($stored)
        || $stored === ''
        || !hash_equals($stored, $submitted)
    ) {
        http_response_code(403);
        exit('Sesi formulir tidak valid atau telah kedaluwarsa. Muat ulang halaman lalu coba kembali.');
    }
}

function requirePostRequest(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Metode permintaan tidak diizinkan.');
    }
}

function normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function limitedText(mixed $value, int $maxLength): string
{
    $text = trim(str_replace("\0", '', is_scalar($value) ? (string) $value : ''));

    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $maxLength, 'UTF-8');
    }

    return substr($text, 0, $maxLength);
}

function hasStrongEnoughPassword(string $password): bool
{
    return strlen($password) >= 10
        && strlen($password) <= 4096
        && preg_match('/[A-Za-z]/', $password) === 1
        && preg_match('/\d/', $password) === 1;
}

function loginThrottleKey(string $email): string
{
    return hash('sha256', normalizeEmail($email));
}

function isLoginTemporarilyBlocked(string $email): bool
{
    $key = loginThrottleKey($email);
    $record = $_SESSION['_login_attempts'][$key] ?? [];
    return (int) ($record['blocked_until'] ?? 0) > time();
}

function recordFailedLogin(string $email): void
{
    $key = loginThrottleKey($email);
    $record = $_SESSION['_login_attempts'][$key] ?? ['count' => 0, 'blocked_until' => 0];
    $record['count'] = (int) $record['count'] + 1;

    if ($record['count'] >= AVES_MAX_LOGIN_ATTEMPTS) {
        $record['count'] = 0;
        $record['blocked_until'] = time() + AVES_LOGIN_LOCK_SECONDS;
    }

    $_SESSION['_login_attempts'][$key] = $record;
}

function clearFailedLogins(string $email): void
{
    unset($_SESSION['_login_attempts'][loginThrottleKey($email)]);
}

secureSessionStart();
