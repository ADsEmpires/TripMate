/**
 * Session Synchronization System
 * File: user/session-sync.js
 * 
 * Ensures session consistency across all pages and storage methods
 */

document.addEventListener('DOMContentLoaded', function() {
    synchronizeSessions();
});

function synchronizeSessions() {
    console.log('Session sync: Starting session synchronization');
    
    // Check all possible session storage locations
    const deviceId = localStorage.getItem('tripmate_active_user_id');
    const deviceName = localStorage.getItem('tripmate_active_user_name');
    const sessionId = sessionStorage.getItem('user_id');
    const sessionName = sessionStorage.getItem('user_name');
    const legacyId = sessionStorage.getItem('userid');
    const legacyName = sessionStorage.getItem('username');
    
    // If device storage exists but session storage is empty, copy from device
    if (deviceId && !sessionId) {
        console.log('Session sync: Copying from device to session storage');
        sessionStorage.setItem('user_id', deviceId);
        if (deviceName) sessionStorage.setItem('user_name', deviceName);
    }
    
    // If session storage exists but device storage is empty, copy to device
    if (sessionId && !deviceId) {
        console.log('Session sync: Copying from session to device storage');
        localStorage.setItem('tripmate_active_user_id', sessionId);
        if (sessionName) localStorage.setItem('tripmate_active_user_name', sessionName);
    }
    
    // Ensure legacy keys are set for compatibility
    const finalId = sessionStorage.getItem('user_id') || deviceId;
    const finalName = sessionStorage.getItem('user_name') || deviceName;
    
    if (finalId) {
        sessionStorage.setItem('userid', finalId);
        if (finalName) sessionStorage.setItem('username', finalName);
        
        // Add the class that user-profile.js checks for
        document.body.classList.add('user-logged-in');
        
        console.log('Session sync: User session synchronized -', finalName);
    } else {
        // Ensure clean state if no user is logged in
        document.body.classList.remove('user-logged-in');
        console.log('Session sync: No user session found');
    }
}

// Periodically check session consistency (every 30 seconds)
setInterval(synchronizeSessions, 30000);