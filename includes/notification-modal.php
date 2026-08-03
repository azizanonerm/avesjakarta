<?php
/**
 * Dialog pemberitahuan bersama untuk validasi dan pesan aplikasi.
 *
 * Variabel opsional:
 * $notification_title   = judul dialog.
 * $notification_message = pesan yang langsung ditampilkan saat halaman dimuat.
 */
$notification_title = trim((string) ($notification_title ?? 'Perhatian'));
$notification_message = trim((string) ($notification_message ?? ''));
?>

<style>
    .app-notification-modal[hidden] { display: none !important; }
    .app-notification-modal {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, 0.58);
        backdrop-filter: blur(2px);
    }
    .app-notification-card {
        width: min(100%, 390px);
        padding: 30px;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
        text-align: center;
    }
    .app-notification-icon {
        display: grid;
        width: 64px;
        height: 64px;
        margin: 0 auto 18px;
        place-items: center;
        border-radius: 50%;
        background: #fee2e2;
        color: #dc2626;
    }
    .app-notification-icon svg { width: 32px; height: 32px; }
    .app-notification-title {
        margin: 0 0 10px;
        color: #0f2d5a;
        font: 700 22px/1.3 "Plus Jakarta Sans", Inter, Arial, sans-serif;
    }
    .app-notification-text { margin: 0 0 24px; color: #64748b; font-size: 15px; line-height: 1.6; }
    .app-notification-button {
        width: 100%;
        min-height: 46px;
        border: 1px solid #0b66c3;
        border-radius: 10px;
        background: #0b66c3;
        color: #fff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }
    .app-notification-button:hover { border-color: #084f99; background: #084f99; }
    @media (max-width: 420px) { .app-notification-card { padding: 24px 20px; } }
</style>

<div id="appNotificationModal" class="app-notification-modal" role="dialog" aria-modal="true" aria-labelledby="appNotificationTitle" aria-describedby="appNotificationText" hidden>
    <div class="app-notification-card">
        <div class="app-notification-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 8v4"></path>
                <path d="M12 16h.01"></path>
            </svg>
        </div>
        <h3 id="appNotificationTitle" class="app-notification-title"><?php echo htmlspecialchars($notification_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h3>
        <p id="appNotificationText" class="app-notification-text"></p>
        <button id="appNotificationButton" type="button" class="app-notification-button">Mengerti</button>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('appNotificationModal');
    const title = document.getElementById('appNotificationTitle');
    const text = document.getElementById('appNotificationText');
    const button = document.getElementById('appNotificationButton');
    let previousFocus = null;

    window.showNotificationModal = function (message, modalTitle) {
        if (!modal || !text || !button) return;
        previousFocus = document.activeElement;
        text.textContent = String(message || 'Terjadi kesalahan. Silakan coba lagi.');
        if (modalTitle && title) title.textContent = modalTitle;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        button.focus();
    };

    window.closeNotificationModal = function () {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
        if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
    };

    button?.addEventListener('click', window.closeNotificationModal);
    modal?.addEventListener('click', function (event) {
        if (event.target === modal) window.closeNotificationModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) window.closeNotificationModal();
    });

    <?php if ($notification_message !== ''): ?>
    window.addEventListener('DOMContentLoaded', function () {
        window.showNotificationModal(<?php echo json_encode($notification_message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
    });
    <?php endif; ?>
})();
</script>
