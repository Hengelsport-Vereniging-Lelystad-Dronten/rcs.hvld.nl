// resources/js/bootstrap.js
// Globale JavaScript-setup (axios instellen en basisheaders).
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF token setup voor alle axios requests
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

const refreshCsrfToken = (csrfToken) => {
    if (!csrfToken) return;

    const metaToken = document.head.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        metaToken.setAttribute('content', csrfToken);
    }

    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
};

const keepSessionAlive = async () => {
    if (document.visibilityState === 'hidden') {
        return;
    }

    try {
        const response = await fetch('/session/keep-alive', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        refreshCsrfToken(data.csrf_token);
    } catch (error) {
        console.warn('Sessie actief houden is mislukt:', error);
    }
};

if (token) {
    window.setInterval(keepSessionAlive, 10 * 60 * 1000);
}
