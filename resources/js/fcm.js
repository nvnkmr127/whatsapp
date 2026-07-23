import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';
import axios from 'axios';

export function initializeFCM() {
    if (!window.FIREBASE_CONFIG || !window.VAPID_KEY) {
        console.warn('Firebase config or VAPID key is missing. Web push notifications will not be initialized.');
        return;
    }

    try {
        const app = initializeApp(window.FIREBASE_CONFIG);
        const messaging = getMessaging(app);

        // Request permission and get token
        Notification.requestPermission().then((permission) => {
            if (permission === 'granted') {
                console.log('Notification permission granted.');
                
                // Register service worker (config is now injected dynamically via Laravel route)
                navigator.serviceWorker.register('/firebase-messaging-sw.js')
                .then((registration) => {
                    getToken(messaging, { 
                        vapidKey: window.VAPID_KEY,
                        serviceWorkerRegistration: registration
                    }).then((currentToken) => {
                        if (currentToken) {
                            sendTokenToServer(currentToken);
                        } else {
                            console.log('No registration token available. Request permission to generate one.');
                        }
                    }).catch((err) => {
                        console.log('An error occurred while retrieving token. ', err);
                    });
                });
            } else {
                console.log('Unable to get permission to notify.');
            }
        });

        // Listen for messages from the service worker (e.g., to play sound when minimized)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data && event.data.type === 'play_sound') {
                    try {
                        const audio = new Audio('/sounds/notification.mp3');
                        audio.play().catch(e => console.log('Audio play prevented by browser policy:', e));
                    } catch (err) {}
                }
            });
        }

        onMessage(messaging, (payload) => {
            console.log('Message received in foreground. ', payload);
            
            // Play custom notification sound
            try {
                const audio = new Audio('/sounds/notification.mp3');
                // Browsers require user interaction before playing audio, so we catch potential errors
                audio.play().catch(e => console.log('Audio play prevented by browser policy:', e));
            } catch (err) {
                console.error('Failed to play notification sound', err);
            }

            // Show OS notification for foreground messages as well
            if (Notification.permission === 'granted') {
                // If we are looking at the chat, we might not want to show it, but as a generic fallback we will show it.
                // The user specifically wants it when looking at other contacts.
                const notificationTitle = payload.notification?.title || payload.data?.title || 'New Message';
                const notificationOptions = {
                    body: payload.notification?.body || payload.data?.body || '',
                    icon: '/favicon.ico',
                    data: payload.data
                };
                new Notification(notificationTitle, notificationOptions);
            }
        });
    } catch (error) {
        console.error('Firebase initialization error', error);
    }
}

function sendTokenToServer(token) {
    const sentToken = localStorage.getItem('fcm_token');
    if (sentToken === token) {
        return; // Token already sent
    }

    axios.post('/web-fcm-token', {
        token: token,
        platform: 'web',
        device_id: navigator.userAgent // use userAgent as a basic device id for web
    }).then(response => {
        console.log('FCM token registered successfully');
        localStorage.setItem('fcm_token', token);
    }).catch(error => {
        console.error('Error registering FCM token:', error);
    });
}
