import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-app.js";
import { getFirestore, collection, getDocs, addDoc, doc, updateDoc, deleteDoc, getDoc } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-firestore.js";
import { getAuth, signInWithEmailAndPassword, onAuthStateChanged, signOut, sendPasswordResetEmail } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-auth.js";
import { getStorage, ref, uploadBytes, getDownloadURL } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-storage.js";

const firebaseConfig = {
  apiKey: "AIzaSyCNJFUyXdKyT46gJqcz92MDEgGd9j7qXP4",
  authDomain: "anjiana-clothing.firebaseapp.com",
  projectId: "anjiana-clothing",
  storageBucket: "anjiana-clothing.firebasestorage.app",
  messagingSenderId: "161221245788",
  appId: "1:161221245788:web:ea21a83449ed11259edf99",
  measurementId: "G-8X4V33DZCX"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

const auth = getAuth(app);
const storage = getStorage(app);

console.log("Firebase initialized.");

export { 
  app, 
  db, collection, getDocs, addDoc, doc, updateDoc, deleteDoc, getDoc,
  auth, signInWithEmailAndPassword, onAuthStateChanged, signOut, sendPasswordResetEmail,
  storage, ref, uploadBytes, getDownloadURL
};

// Caching helper for general store settings
export async function getCachedSettings() {
    let settings = sessionStorage.getItem('store_settings');
    if (!settings) {
        try {
            const docRef = doc(db, "settings", "store_info");
            const docSnap = await getDoc(docRef);
            if (docSnap.exists()) {
                settings = JSON.stringify(docSnap.data());
                sessionStorage.setItem('store_settings', settings);
            }
        } catch (e) {
            console.error("Error fetching settings: ", e);
        }
    }
    return settings ? JSON.parse(settings) : { currency: "Rs.", taxRate: 0 };
}

// Caching helper for shipping rules settings
export async function getCachedShippingRules() {
    let rules = sessionStorage.getItem('shipping_rules');
    if (!rules) {
        try {
            const docRef = doc(db, "settings", "shipping_rules");
            const docSnap = await getDoc(docRef);
            if (docSnap.exists()) {
                rules = JSON.stringify(docSnap.data());
                sessionStorage.setItem('shipping_rules', rules);
            }
        } catch (e) {
            console.error("Error fetching shipping rules: ", e);
        }
    }
    return rules ? JSON.parse(rules) : { standardFee: 10, expressFee: 25, freeShippingThreshold: 150 };
}

