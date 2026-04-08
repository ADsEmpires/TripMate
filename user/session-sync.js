/**
 * Session Synchronization System
 * File: user/session-sync.js
 * 
 * Ensures session consistency across all pages and storage methods
 */

document.addEventListener('DOMContentLoaded', function() {
    synchronizeSessions();
    
    // Also run when storage changes (for multi-tab support)
    window.addEventListener('storage', function(e) {
        if (e.key === 'tripmate_active_user_id' || e.key === 'user_id' || e.key === 'userid') {
            console.log('Session sync: Storage changed, re-synchronizing');
            synchronizeSessions();
        }
    });
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
    
    // Check PHP session via server-side flag
    const phpSessionId = document.body.getAttribute('data-user-id') || 
                         document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    
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
    const finalId = sessionStorage.getItem('user_id') || deviceId || legacyId;
    const finalName = sessionStorage.getItem('user_name') || deviceName || legacyName;
    
    if (finalId) {
        sessionStorage.setItem('userid', finalId);
        if (finalName) sessionStorage.setItem('username', finalName);
        
        // Add the class that user-profile.js checks for
        document.body.classList.add('user-logged-in');
        
        // Also set a data attribute for server-side checks
        document.body.setAttribute('data-user-id', finalId);
        
        console.log('Session sync: User session synchronized -', finalName);
    } else {
        // Ensure clean state if no user is logged in
        document.body.classList.remove('user-logged-in');
        document.body.removeAttribute('data-user-id');
        console.log('Session sync: No user session found');
    }
    
    // Dispatch event for other scripts to listen to
    const event = new CustomEvent('sessionSynced', { 
        detail: { 
            userId: finalId, 
            userName: finalName,
            isLoggedIn: !!finalId 
        } 
    });
    document.dispatchEvent(event);
}

// Periodically check session consistency (every 30 seconds)
setInterval(synchronizeSessions, 30000);

// Add cross-tab/page communication for session sync
window.addEventListener('storage', function(e) {
    if (e.key === 'tripmate_active_user_id' || e.key === 'tripmate_active_user_name') {
        console.log('Session sync: Storage changed in another tab, re-synchronizing');
        synchronizeSessions();
        
        // Dispatch event for other scripts in this tab
        const syncEvent = new CustomEvent('sessionSynced', { 
            detail: { 
                userId: localStorage.getItem('tripmate_active_user_id'),
                userName: localStorage.getItem('tripmate_active_user_name'),
                isLoggedIn: !!localStorage.getItem('tripmate_active_user_id')
            } 
        });
        document.dispatchEvent(syncEvent);
    }
});

// Add beforeunload handler to ensure session cleanup on tab close
window.addEventListener('beforeunload', function() {
    // Don't clear on refresh, only on actual navigation away
    // This is handled by the server-side session timeout
});