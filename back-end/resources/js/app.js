import './charts.js';
import { t } from './i18n.js';

/**
 * Styled file dropzones — show the chosen filename so the control reads as
 * "upload something" rather than a bare browser file input.
 */
function bindFileDrops(root = document) {
    root.querySelectorAll('[data-file-drop]').forEach((wrap) => {
        if (wrap.dataset.bound === '1') return;
        wrap.dataset.bound = '1';

        const input = wrap.querySelector('[data-file-drop-input]');
        const nameEl = wrap.querySelector('[data-file-drop-name]');
        const titleEl = wrap.querySelector('[data-file-drop-title]');
        const subEl = wrap.querySelector('[data-file-drop-sub]');
        if (!input) return;

        const defaultTitle = titleEl?.textContent || '';
        const defaultSub = subEl?.textContent || '';

        const sync = () => {
            const file = input.files && input.files[0];
            wrap.classList.toggle('has-file', !!file);
            wrap.classList.toggle('is-drag', false);
            if (file && nameEl) {
                nameEl.hidden = false;
                nameEl.textContent = file.name;
                if (titleEl) titleEl.textContent = t('File ready to upload');
                if (subEl) {
                    const kb = Math.max(1, Math.round(file.size / 1024));
                    subEl.textContent = kb >= 1024
                        ? (kb / 1024).toFixed(1) + ' MB'
                        : kb + ' KB';
                }
            } else if (nameEl) {
                nameEl.hidden = true;
                nameEl.textContent = '';
                if (titleEl) titleEl.textContent = defaultTitle;
                if (subEl) subEl.textContent = defaultSub;
            }
        };

        input.addEventListener('change', sync);

        ['dragenter', 'dragover'].forEach((evt) => {
            wrap.addEventListener(evt, (e) => {
                e.preventDefault();
                wrap.classList.add('is-drag');
            });
        });
        ['dragleave', 'drop'].forEach((evt) => {
            wrap.addEventListener(evt, (e) => {
                e.preventDefault();
                wrap.classList.remove('is-drag');
            });
        });
        wrap.addEventListener('drop', (e) => {
            const files = e.dataTransfer?.files;
            if (!files || !files.length) return;
            try {
                const dt = new DataTransfer();
                dt.items.add(files[0]);
                input.files = dt.files;
            } catch (_) {
                /* fall back: user can still click Choose file */
            }
            sync();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        sync();
    });
}

document.addEventListener('DOMContentLoaded', () => bindFileDrops());
window.etpbBindFileDrops = bindFileDrops;
