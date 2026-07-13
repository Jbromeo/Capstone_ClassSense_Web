import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

const firebaseConfig = {
    apiKey: "AIzaSyA9rXCyXcOrKrIj4tssFh2weSJTlhiDjUU",
    authDomain: "class-sense-9def0.firebaseapp.com",
    projectId: "class-sense-9def0",
    storageBucket: "class-sense-9def0.firebasestorage.app",
    messagingSenderId: "537462109705",
    appId: "1:537462109705:web:1c156db52f7864a2cd2ad8"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

const secondaryApp = initializeApp(firebaseConfig, "SecondaryInstance");
const secondaryAuth = getAuth(secondaryApp);

export { auth, secondaryAuth };
