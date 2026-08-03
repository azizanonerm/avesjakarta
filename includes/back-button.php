<?php
/**
 * Tombol kembali global yang ditempatkan di samping identitas halaman.
 *
 * Variabel opsional sebelum include:
 * $back_url   = tujuan cadangan jika halaman dibuka langsung.
 * $back_label = teks tujuan cadangan untuk aria-label/title.
 * $back_use_history = gunakan riwayat browser jika masih di aplikasi yang sama.
 */
$back_url = $back_url ?? 'index.html';
$back_label = $back_label ?? 'halaman sebelumnya';
$back_use_history = $back_use_history ?? true;
?>
<style id="aves-global-back-style">
    .aves-header-back-slot {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
    }

    .aves-header-back-slot:not(.is-mounted) {
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 9998;
    }

    .aves-global-back {
        display: inline-flex;
        width: 104px;
        height: 40px;
        min-height: 40px;
        flex: 0 0 104px;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: .5rem .75rem;
        border: 1px solid rgba(0, 88, 190, .22);
        border-radius: 9px;
        background: #fff;
        color: #00458d;
        font-family: Inter, Arial, sans-serif;
        font-size: .8125rem;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        box-shadow: 0 3px 10px rgba(15, 45, 90, .12);
        transition: background-color .15s ease, border-color .15s ease;
    }

    .aves-global-back:hover {
        background: #eef4ff;
        border-color: rgba(0, 88, 190, .38);
    }

    .aves-header-back-slot.is-dark .aves-global-back {
        border-color: rgba(255, 255, 255, .28);
        background: rgba(255, 255, 255, .08);
        color: #fff;
        box-shadow: none;
    }

    .aves-header-back-slot.is-dark .aves-global-back:hover {
        border-color: rgba(255, 255, 255, .45);
        background: rgba(255, 255, 255, .16);
    }

    .aves-global-back:focus-visible {
        outline: 3px solid rgba(84, 152, 255, .45);
        outline-offset: 2px;
    }
    .aves-global-back__icon {
        display: inline-flex;
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        line-height: 1;
    }

    @media (max-width: 640px) {
        .aves-header-back-slot .aves-global-back {
            width: 40px;
            flex-basis: 40px;
            padding: 0;
        }

        .aves-global-back__label {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
        }
    }

    @media print {
        .aves-header-back-slot { display: none !important; }
    }
</style>

<div class="aves-header-back-slot" data-aves-back-slot>
    <a
        class="aves-global-back"
        href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>"
        title="Kembali ke <?php echo htmlspecialchars($back_label, ENT_QUOTES, 'UTF-8'); ?>"
        aria-label="Kembali ke <?php echo htmlspecialchars($back_label, ENT_QUOTES, 'UTF-8'); ?>"
        <?php if ($back_use_history): ?>
        onclick="return avesGoBack(event);"
        <?php endif; ?>
    >
        <span class="aves-global-back__icon" aria-hidden="true">&larr;</span>
        <span class="aves-global-back__label">Kembali</span>
    </a>
</div>

<script>
if (typeof window.avesGoBack !== 'function') {
    window.avesGoBack = function (event) {
        try {
            const referrer = document.referrer ? new URL(document.referrer) : null;
            const sameApplication = referrer
                && referrer.origin === window.location.origin;

            if (sameApplication && window.history.length > 1) {
                event.preventDefault();
                window.history.back();
                return false;
            }
        } catch (error) {
            // Jika referrer tidak dapat dibaca, tautan cadangan tetap digunakan.
        }

        return true;
    };
}

(function mountAvesBackButton() {
    const slot = document.querySelector('[data-aves-back-slot]');
    const host = document.querySelector('[data-aves-back-host]');

    if (!slot || !host) {
        return;
    }

    if (host.dataset.avesBackTheme === 'dark') {
        slot.classList.add('is-dark');
    }

    if (host.dataset.avesBackPosition === 'end') {
        host.append(slot);
    } else {
        host.prepend(slot);
    }

    slot.classList.add('is-mounted');
})();
</script>
