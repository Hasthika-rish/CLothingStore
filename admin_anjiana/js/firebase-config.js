import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-app.js";
import { getFirestore, collection, getDocs, addDoc, doc, updateDoc, deleteDoc, getDoc, setDoc, query, where, orderBy, limit } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-firestore.js";
import { getAuth, signInWithEmailAndPassword, onAuthStateChanged, signOut, sendPasswordResetEmail } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-auth.js";
import { getStorage, ref, uploadBytes, getDownloadURL } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-storage.js";

const firebaseConfig = {
  apiKey: "AIzaSyDgJ0sj01njyGsBjC8MNDZtqxkE-UPslwE",
  authDomain: "sage-anjiana.firebaseapp.com",
  projectId: "sage-anjiana",
  storageBucket: "sage-anjiana.firebasestorage.app",
  messagingSenderId: "981867891187",
  appId: "1:981867891187:web:22a7bb93f15c5527e6ea86",
  measurementId: "G-K3BDHVBEDM"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

const auth = getAuth(app);
const storage = getStorage(app);

console.log("Firebase initialized.");

export { 
  app, 
  db, collection, getDocs, addDoc, doc, updateDoc, deleteDoc, getDoc, setDoc, query, where, orderBy, limit,
  auth, signInWithEmailAndPassword, onAuthStateChanged, signOut, sendPasswordResetEmail,
  storage, ref, uploadBytes, getDownloadURL
};
