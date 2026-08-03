<?php
/**
 * Metadata PWA bersama untuk seluruh halaman PHP.
 */

if (!function_exists('avesPwaBasePath')) {
    function avesPwaBasePath(): string
    {
        $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));

        if (preg_match('#^(.*?)/admin(?:/|$)#', $scriptPath, $matches)) {
            $basePath = $matches[1];
        } else {
            $basePath = dirname($scriptPath);
        }

        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        if ($basePath === '' || $basePath === '.') {
            return '/';
        }

        return $basePath . '/';
    }
}

if (!function_exists('renderAvesPwaHead')) {
    function renderAvesPwaHead(): void
    {
        $basePath = htmlspecialchars(avesPwaBasePath(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?>
    <link rel="manifest" href="<?php echo $basePath; ?>manifest.json">
    <meta name="theme-color" content="#0f2d5a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="AVES">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $basePath; ?>assets/icons/aves-blue-192.png">
    <link rel="apple-touch-icon" href="<?php echo $basePath; ?>assets/icons/aves-blue-192.png">
    <script defer src="<?php echo $basePath; ?>assets/pwa-register.js"></script>
        <?php
    }
}
