import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

const key = import.meta.env.VITE_REVERB_APP_KEY;
const host = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const port = import.meta.env.VITE_REVERB_PORT;
const scheme = import.meta.env.VITE_REVERB_SCHEME || (window.location.protocol === 'https:' ? 'https' : 'http');

console.log('Echo Config:', {
    key,
    host,
    port,
    scheme,
    env: import.meta.env
});

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: key,
    wsHost: host,
    wsPort: port || 80,
    wssPort: port || 443,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Log and dispatch for components waiting for Echo
console.log('Laravel Echo initialized with Reverb broadcaster.');
window.dispatchEvent(new CustomEvent('echo-ready'));
