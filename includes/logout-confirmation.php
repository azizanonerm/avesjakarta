<?php
/**
 * Modal konfirmasi keluar bersama.
 *
 * Variabel opsional:
 * $logout_url = alamat tujuan logout.
 */
$logout_url = $logout_url ?? 'logout.php';
?>

<style>
    .logout-confirmation-modal[hidden] {
        display: none !important;
    }

    .logout-confirmation-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, 0.58);
        backdrop-filter: blur(2px);
    }

    .logout-confirmation-card {
        width: min(100%, 390px);
        padding: 30px;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
        text-align: center;
    }

    .logout-confirmation-icon {
        display: grid;
        width: 64px;
        height: 64px;
        margin: 0 auto 18px;
        place-items: center;
        border-radius: 50%;
        background: #fee2e2;
        color: #dc2626;
    }

    .logout-confirmation-icon svg {
        width: 32px;
        height: 32px;
    }

    .logout-confirmation-title {
        margin: 0 0 10px;
        color: #0f2d5a;
        font-family: "Plus Jakarta Sans", Inter, Arial, sans-serif;
        font-size: 22px;
        font-weight: 700;
        line-height: 1.3;
    }

    .logout-confirmation-text {
        margin: 0 0 24px;
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
    }

    .logout-confirmation-actions {
        display: flex;
        gap: 12px;
    }

    .logout-confirmation-button {
        display: inline-flex;
        flex: 1;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        margin: 0;
        padding: 10px 16px;
        border-radius: 10px;
        font: inherit;
        font-weight: 700;
        line-height: 1.2;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.18s ease, border-color 0.18s ease;
    }

    .logout-confirmation-button--cancel {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
    }

    .logout-confirmation-button--cancel:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .logout-confirmation-button--confirm {
        border: 1px solid #dc2626;
        background: #dc2626;
        color: #ffffff;
    }

    .logout-confirmation-button--confirm:hover {
        border-color: #b91c1c;
        background: #b91c1c;
    }

    @media (max-width: 420px) {
        .logout-confirmation-card {
            padding: 24px 20px;
        }
    }
</style>

<div
    id="logoutModal"
    class="logout-confirmation-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="logoutModalTitle"
    hidden
>
    <div class="logout-confirmation-card">
        <div class="logout-confirmation-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 17l5-5-5-5"></path>
                <path d="M15 12H3"></path>
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
            </svg>
        </div>
        <h3 id="logoutModalTitle" class="logout-confirmation-title">Konfirmasi Keluar</h3>
        <p class="logout-confirmation-text">Apakah Anda yakin ingin keluar dari sistem?</p>

        <div class="logout-confirmation-actions">
            <button
                type="button"
                class="logout-confirmation-button logout-confirmation-button--cancel"
                onclick="closeLogoutModal()"
            >
                Batal
            </button>
            <a
                id="confirmLogoutBtn"
                href="<?php echo htmlspecialchars($logout_url, ENT_QUOTES, 'UTF-8'); ?>"
                class="logout-confirmation-button logout-confirmation-button--confirm"
            >
                Ya, Keluar
            </a>
        </div>
    </div>
</div>

<script>
if (typeof window.confirmLogout !== 'function') {
    window.confirmLogout = function (logoutUrl, event) {
        if (event) {
            event.preventDefault();
        }

        const modal = document.getElementById('logoutModal');
        const confirmButton = document.getElementById('confirmLogoutBtn');

        if (!modal || !confirmButton) {
            window.location.href = logoutUrl;
            return false;
        }

        confirmButton.href = logoutUrl;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        return false;
    };
}

if (typeof window.closeLogoutModal !== 'function') {
    window.closeLogoutModal = function () {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.hidden = true;
        }
        document.body.style.overflow = '';
    };
}

document.addEventListener('click', function (event) {
    const modal = document.getElementById('logoutModal');
    if (modal && event.target === modal) {
        closeLogoutModal();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeLogoutModal();
    }
});
</script>
