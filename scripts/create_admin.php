<?php
/**
 * Membuat atau memperbarui administrator tanpa menyimpan password di source.
 *
 * Wajib dijalankan melalui CLI dengan environment:
 * AVES_ADMIN_EMAIL, AVES_ADMIN_NAME, AVES_ADMIN_PASSWORD.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Akses ditolak.');
}

require_once dirname(__DIR__) . '/config/database.php';

$email = normalizeEmail((string) getenv('AVES_ADMIN_EMAIL'));
$name = limitedText(getenv('AVES_ADMIN_NAME') ?: 'Administrator', 100);
$password = (string) getenv('AVES_ADMIN_PASSWORD');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "AVES_ADMIN_EMAIL tidak valid.\n");
    exit(1);
}

if (!hasStrongEnoughPassword($password)) {
    fwrite(
        STDERR,
        "AVES_ADMIN_PASSWORD minimal 10 karakter dan harus mengandung huruf serta angka.\n"
    );
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$existing = fetchOne('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);

if ($existing) {
    execute(
        'UPDATE users SET nama = ?, password = ?, role = ? WHERE id = ?',
        [$name, $passwordHash, 'admin', (int) $existing['id']]
    );
    fwrite(STDOUT, "Administrator berhasil diperbarui.\n");
} else {
    execute(
        'INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)',
        [$name, $email, $passwordHash, 'admin']
    );
    fwrite(STDOUT, "Administrator berhasil dibuat.\n");
}

