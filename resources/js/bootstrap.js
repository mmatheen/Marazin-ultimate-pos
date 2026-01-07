/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Handle session expiration globally for all axios requests
 */
window.axios.interceptors.response.use(
    response => response,
    error => {
        // Handle 401 (Unauthenticated) and 419 (CSRF Token Mismatch)
        if (error.response && (error.response.status === 401 || error.response.status === 419)) {
            // Show a message if available
            const message = error.response.data?.message || 'Your session has expired. Please log in again.';
            
            // Use toastr if available, otherwise alert
            if (typeof toastr !== 'undefined') {
                toastr.warning(message);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Session Expired',
                    text: message,
                    confirmButtonText: 'Login'
                }).then(() => {
                    window.location.href = error.response.data?.redirect || '/login';
                });
                return Promise.reject(error);
            } else {
                alert(message);
            }
            
            // Redirect to login page after a short delay
            setTimeout(() => {
                window.location.href = error.response.data?.redirect || '/login';
            }, 1500);
        }
        
        return Promise.reject(error);
    }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
