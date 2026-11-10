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
        if (!input) return;
        const query = input.value.trim();
        if (!query) return;

        // Redirect directly to the dedicated non-sensitive public track route
        window.location.href = `/track/${encodeURIComponent(query)}`;
    };
}

