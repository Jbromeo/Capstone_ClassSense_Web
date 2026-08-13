// firebase-messaging-sw.js
// ClassSense web push service worker (background delivery for FCM).
// Must be served from the ClassSense web root: /ClassSense/firebase-messaging-sw.js
// Registered as an ES module (type: 'module') to match the v11 modular SDK used on the page.
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
import { getMessaging, onBackgroundMessage } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-messaging-sw.js";

const app = initializeApp({
    apiKey: "AIzaSyA9rXCyXcOrKrIj4tssFh2weSJTlhiDjUU",
    authDomain: "class-sense-9def0.firebaseapp.com",
    projectId: "class-sense-9def0",
    storageBucket: "class-sense-9def0.firebasestorage.app",
    messagingSenderId: "537462109705",
    appId: "1:537462109705:web:1c156db52f7864a2cd2ad8"
});

const messaging = getMessaging(app);

onBackgroundMessage(messaging, function (payload) {
    const data = payload.data || {};
    const title = (payload.notification && payload.notification.title) || 'ClassSense';
    const options = {
        body: (payload.notification && payload.notification.body) || '',
        icon: '/ClassSense/assets/classsense-logo.png',
        badge: '/ClassSense/assets/classsense-logo.png',
        data: data
    };
    return self.registration.showNotification(title, options);
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const link = event.notification.data && event.notification.data.link;
    const url = link || '/ClassSense/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i++) {
                if ('focus' in clientList[i]) {
                    return clientList[i].focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});
