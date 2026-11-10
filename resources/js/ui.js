/**
 * Client UI Interactions: Modals, Popovers, Currency Converter, Demo Login
 */

export function initUiModule() {
    // 1-Click Demo Login Helper
    window.autoLogin = function (email) {
        const emailInput = document.getElementById('email');
        const passInput = document.getElementById('password');
        const form = document.querySelector('form');

        if (emailInput && passInput && form) {
            emailInput.value = email;
            passInput.value = 'password';
            form.submit();
        }
    };

    // Quick Currency Converter in Dashboard
    window.convertDashCurrency = function () {
        const amountEl = document.getElementById('dash-curr-amount');
        const fromEl = document.getElementById('dash-curr-from');
        const resultEl = document.getElementById('dash-curr-result');

        if (!amountEl || !fromEl || !resultEl) return;

        const amount = parseFloat(amountEl.value) || 1000;
        const from = fromEl.value || 'EUR';

        resultEl.innerHTML = `<span class="animate-pulse text-slate-400">Converting...</span>`;

        fetch(`/api/v1/integrations/currency?amount=${amount}&from=${from}&to=USD`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.data && data.data.converted_amount) {
                const val = Number(data.data.converted_amount).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                resultEl.innerText = `≈ $${val} USD`;
            } else {
                throw new Error('Fallback needed');
            }
        })
        .catch(() => {
            const rates = { EUR: 1.086, IDR: 0.000063, SGD: 0.746, CNY: 0.138, AED: 0.272, AUD: 0.655 };
            const est = amount * (rates[from] || 1);
            resultEl.innerText = `≈ $${est.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} USD`;
        });
    };

    // Notification dropdown close on outside click
    document.addEventListener('click', (e) => {
        const notifBtn = document.getElementById('notif-btn');
        const notifMenu = document.getElementById('notif-menu');
        if (notifBtn && notifMenu && !notifBtn.contains(e.target) && !notifMenu.contains(e.target)) {
            notifMenu.classList.add('hidden');
        }
    });

    // Mobile nav toggle
    window.toggleMobileNav = function () {
        const drawer = document.getElementById('mobile-nav-drawer');
        if (drawer) {
            drawer.classList.toggle('hidden');
        }
    };
}

