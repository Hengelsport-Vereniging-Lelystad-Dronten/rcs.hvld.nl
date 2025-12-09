// resources/js/bootstrap.js
// Globale JavaScript-setup (axios instellen en basisheaders).
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
