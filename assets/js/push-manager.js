// push-manager.js
// Client-side FCM push notification management. Reuses the Firebase app already
// created by custom-auth.js (auth.app) to avoid duplicate-app conflicts.
import { auth } from './custom-auth.js';
import {
    getMessaging,
    getToken,
    deleteToken,
    onMessage
} from "https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging.js";

const CS_ROOT = window.CS_ROOT || '/ClassSense/';
const VAPID_KEY = 'BMZfdzG6J9wY1fLwoAtKJYUlCIvDCTA5vS0H8XweJTIIeRbihrWPeASrPAoqbFIjJ6vaH2zrDRc6UNBMED5qMP0';

let messaging = null;

function getMessagingInstance() {
    if (!messaging) messaging = getMessaging(auth.app);
    return messaging;
}

function browserSupportError() {
    if (!('Notification' in window)) return 'This browser does not support notifications.';
    if (!('serviceWorker' in navigator)) return 'This browser does not support service workers.';
    return null;
}

async function ensureServiceWorker() {
    const reg = await navigator.serviceWorker.register(CS_ROOT + 'firebase-messaging-sw.js', { type: 'module' });
    await navigator.serviceWorker.ready;
    return reg;
}

// Enable push: request permission, register the SW, obtain an FCM token.
export async function enablePush() {
    const unsupported = browserSupportError();
    if (unsupported) throw new Error(unsupported);

    if (Notification.permission === 'denied') {
        throw new Error('Notifications are blocked. Allow them in your browser site settings, then try again.');
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        throw new Error('Notification permission was not granted.');
    }

    const reg = await ensureServiceWorker();
    const token = await getToken(getMessagingInstance(), {
        vapidKey: VAPID_KEY,
        serviceWorkerRegistration: reg
    });
    if (!token) throw new Error('Could not obtain a push token.');
    return token;
}

// Disable push: delete the token from Firebase and the local SW subscription.
export async function disablePush() {
    try {
        await deleteToken(getMessagingInstance());
    } catch (e) {
        console.warn('[push-manager] deleteToken failed:', e);
    }
    try {
        const regs = await navigator.serviceWorker.getRegistrations();
        for (const reg of regs) {
            if (reg.active && reg.active.scriptURL.endsWith('firebase-messaging-sw.js')) {
                await reg.unregister();
            }
        }
    } catch (e) {
        console.warn('[push-manager] SW unregister failed:', e);
    }
}

export async function savePushSettings(enabled, token) {
    const res = await fetch(CS_ROOT + 'api/push_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ enabled: enabled, token: token || '' })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Failed to save push settings');
    return data;
}

export async function loadPushSettings() {
    const res = await fetch(CS_ROOT + 'api/push_settings.php');
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Failed to load push settings');
    return data;
}

// Foreground delivery: FCM fires this handler while the page is open.
export function onForegroundMessage(cb) {
    try {
        onMessage(getMessagingInstance(), (payload) => cb(payload));
    } catch (e) {
        console.warn('[push-manager] onMessage setup failed:', e);
    }
}
