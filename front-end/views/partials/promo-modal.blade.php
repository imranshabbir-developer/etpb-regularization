{{--
    Full-page promo modal (jobs.punjab.gov.pk style).
    Shows on every load of pages that include this partial.
    Closes on: X button, backdrop click, Escape.
    Image fits browser height without stretching (object-fit: contain).
--}}
<div class="promo-modal" id="etpbPromoModal" role="dialog" aria-modal="true"
     aria-label="{{ __('Evacuee Trust Property Board — Property Regularization') }}" hidden>
    <div class="promo-modal-backdrop" data-promo-close tabindex="-1"></div>

    <div class="promo-modal-dialog" role="document">
        <button type="button" class="promo-modal-close" data-promo-close
                aria-label="{{ __('Close announcement') }}">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
            </svg>
        </button>

        <div class="promo-modal-frame">
            <img
                src="{{ asset('assets/img/maryam_nawaz_2.jpg') }}"
                alt="{{ __('Government of Punjab — Evacuee Trust Property Board Property Regularization. Apply online now to regularize your possession.') }}"
                class="promo-modal-img"
                width="900"
                height="1200"
                decoding="async"
                fetchpriority="high"
            >
        </div>
    </div>
</div>

<style>
/* Scoped here so login (which may not load every app component) always has it. */
.promo-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: grid;
    place-items: center;
    padding: clamp(.5rem, 2vw, 1.25rem);
}
.promo-modal[hidden] { display: none !important; }
.promo-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(8, 18, 36, .62);
    backdrop-filter: blur(3px);
    cursor: pointer;
}
.promo-modal-dialog {
    position: relative;
    z-index: 1;
    max-width: min(92vw, 560px);
    max-height: min(92vh, 920px);
    width: auto;
    animation: promo-rise .35s ease-out both;
}
@keyframes promo-rise {
    from { opacity: 0; transform: translateY(12px) scale(.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.promo-modal-frame {
    display: block;
    border-radius: 1rem;
    overflow: hidden;
    background: var(--surface-raised, #fff);
    box-shadow:
        0 24px 64px rgba(8, 18, 36, .45),
        0 0 0 1px rgba(255, 255, 255, .12);
    line-height: 0;
}
:root[data-theme="dark"] .promo-modal-frame {
    box-shadow:
        0 24px 64px rgba(0, 0, 0, .55),
        0 0 0 1px rgba(255, 255, 255, .08);
}
.promo-modal-img {
    display: block;
    width: auto;
    height: auto;
    max-width: min(92vw, 560px);
    max-height: min(88vh, 860px);
    object-fit: contain;
    object-position: center top;
}
.promo-modal-close {
    position: absolute;
    top: -.55rem;
    right: -.55rem;
    z-index: 2;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    border: 0;
    cursor: pointer;
    display: grid;
    place-items: center;
    color: var(--text-c, #162033);
    background: var(--surface-raised, #fff);
    box-shadow: 0 6px 18px rgba(8, 18, 36, .28);
    transition: transform .15s, background .15s;
}
.promo-modal-close:hover {
    transform: scale(1.06);
    background: var(--tint, #F3F7FD);
}
@media (max-width: 480px) {
    .promo-modal-dialog { max-width: 94vw; }
    .promo-modal-img { max-width: 94vw; max-height: 82vh; }
    .promo-modal-close { top: .35rem; right: .35rem; }
}
</style>

<script>
(function () {
    var modal = document.getElementById('etpbPromoModal');
    if (!modal) return;

    function openPromo() {
        modal.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        var btn = modal.querySelector('.promo-modal-close');
        if (btn) btn.focus({ preventScroll: true });
    }

    function closePromo() {
        modal.hidden = true;
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
    }

    modal.querySelectorAll('[data-promo-close]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            closePromo();
        });
    });

    // Clicking the image itself does not close; only backdrop / X.
    // Escape closes.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closePromo();
    });

    // Show on every load/refresh of this page.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', openPromo);
    } else {
        openPromo();
    }
})();
</script>
