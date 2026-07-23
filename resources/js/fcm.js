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
                
                // Pass config to the service worker registration
                const configQuery = new URLSearchParams(window.FIREBASE_CONFIG).toString();
                
                navigator.serviceWorker.register(`/firebase-messaging-sw.js?${configQuery}`)
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
