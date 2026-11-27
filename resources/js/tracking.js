/**
 * Public & Quick Tracking Client Logic
 */

export function initTrackingModule() {
    window.fillTrack = function (num) {
        const input = document.getElementById('public-track-input');
        if (input) {
            input.value = num;
            handlePublicTrack();
        }
    };

    window.handlePublicTrack = function () {
        const input = document.getElementById('public-track-input');
        const errorEl = document.getElementById('public-track-error');
        if (!input) return;
        const query = input.value.trim();
        if (!query) {
            if (errorEl) {
                errorEl.classList.remove('hidden');
            }
            input.setAttribute('aria-invalid', 'true');
            input.focus();

            return;
        }

        if (errorEl) {
            errorEl.classList.add('hidden');
        }
        input.removeAttribute('aria-invalid');

        // Redirect directly to the dedicated non-sensitive public track route
        window.location.href = `/track/${encodeURIComponent(query)}`;
    };

    const bindTrackInput = () => {
        const input = document.getElementById('public-track-input');
        const errorEl = document.getElementById('public-track-error');
        if (!input || input.dataset.trackBound === '1') return;

        input.dataset.trackBound = '1';
        input.addEventListener('input', () => {
            if (input.value.trim() !== '') {
                if (errorEl) {
                    errorEl.classList.add('hidden');
                }
                input.removeAttribute('aria-invalid');
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindTrackInput);
    } else {
        bindTrackInput();
    }
}

