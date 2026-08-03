<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/config/security.php';
destroySecureSession();

header('Location: index.html');
exit;
?>
