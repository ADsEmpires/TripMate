/**
 * Session Synchronization System
 * File: user/session-sync.js
 * 
 * Purpose:
 * - Validate session on page load
 * - Sync session data across browser tabs
 * - Trust PHP-rendered session data
 */

document.addEventListener('DOMContentLoaded', function() {
    // Wait for DOM to be fully ready
    setTimeout(() => {
        validateSessionOnLoad();
    }, 100);
});

/**
 * Validate session on page load
 * CRITICAL: Check PHP-rendered meta tags first
 */
async function validateSessionOnLoad() {
    console.log('SessionSync: Validating session on page load...');
    
    // Step 1: Check PHP meta tags (server-side validation)
    const metaUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    const metaUserName = document.querySelector('meta[name="user-name"]')?.getAttribute('content');
    
    if (metaUserId) {
        console.log('✅ SessionSync: Server-side session confirmed for user:', metaUserName);
        syncSessionFromPHP(metaUserId, metaUserName);
        return;
    }
    
    // Step 2: If no meta tags, check if we have stored session
    const storedUserId = localStorage.getItem('tripmate_active_user_id') || 
                         sessionStorage.getItem('user_id');
    
    if (!storedUserId) {
        console.log('SessionSync: No session found');
        clearAllSessionData();
        return;
    }
    
    // Step 3: Validate stored session with server
    console.log('SessionSync: Validating stored session...');
    const isValid = await validateSessionWithServer();
    
    if (isValid) {
        console.log('✅ SessionSync: Stored session validated');
        syncSessionFromStorage();
    } else {
        console.log('❌ SessionSync: Stored session is invalid');
        clearAllSessionData();
    }
}

/**
 * Sync session from PHP meta tags
 */
function syncSessionFromPHP(userId, userName) {
    console.log('SessionSync: Syncing from PHP for user:', userId);
    
    // Update all storage locations
    localStorage.setItem('tripmate_active_user_id', userId);
    localStorage.setItem('tripmate_active_user_name', userName || '');
    
    sessionStorage.setItem('user_id', userId);
    sessionStorage.setItem('userid', userId);
    sessionStorage.setItem('user_name', userName || '');
    sessionStorage.setItem('username', userName || '');
    
    // Update body attributes
    document.body.classList.add('user-logged-in');
    document.body.setAttribute('data-user-id', userId);
    if (userName) {
        document.body.setAttribute('data-user-name', userName);
    }
    
    // Dispatch event
    dispatchSessionEvent(true, { id: userId, name: userName });
}

/**
 * Sync session from local storage
 */
function syncSessionFromStorage() {
    const userId = localStorage.getItem('tripmate_active_user_id') || 
                   sessionStorage.getItem('user_id');
    const userName = localStorage.getItem('tripmate_active_user_name') || 
                     sessionStorage.getItem('user_name');
    
    if (userId) {
        console.log('SessionSync: Syncing from storage for user:', userId);
        syncSessionFromPHP(userId, userName);
    } else {
        clearAllSessionData();
    }
}

/**
 * Validate session with server
 */
async function validateSessionWithServer() {
    try {
        const baseUrl = getBaseUrl();
        const response = await fetch(baseUrl + '/user/session_refresh.php?action=check&t=' + Date.now(), {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        
        if (response.status === 401) {
            return false;
        }
        
        if (response.ok) {
            const data = await response.json();
            return data.success && data.is_valid;
        }
        
        return false;
    } catch (error) {
        console.warn('SessionSync: Validation error -', error.message);
        // Don't treat network errors as invalid session
        return true;
    }
}

/**
 * Get base URL
 */
function getBaseUrl() {
    const metaBase = document.querySelector('meta[name="api-base"]')?.getAttribute('content');
    if (metaBase) {
        return metaBase.replace(/\/$/, '');
    }

    let path = window.location.pathname;
    path = path.replace(/\/(user|auth|main|search|bookings|admin|dashboard|Contributor|database|config|actions|image|uploads|css|js|scripts)(\/.*)?$/, '');
    path = path.replace(/\/$/, '');

    return window.location.origin + path;
}

/**
 * Dispatch session sync event
 */
function dispatchSessionEvent(isLoggedIn, userData = null) {
    const event = new CustomEvent('sessionSynced', {
        detail: {
            isLoggedIn,
            userData,
            timestamp: Date.now()
        }
    });
    document.dispatchEvent(event);
}

/**
 * Clear all session data
 */
function clearAllSessionData() {
    console.log('SessionSync: Clearing all session data');
    
    localStorage.removeItem('tripmate_active_user_id');
    localStorage.removeItem('tripmate_active_user_name');
    localStorage.removeItem('tripmate_user_email');
    
    sessionStorage.removeItem('user_id');
    sessionStorage.removeItem('userid');
    sessionStorage.removeItem('user_name');
    sessionStorage.removeItem('username');
    
    document.body.classList.remove('user-logged-in');
    document.body.removeAttribute('data-user-id');
    document.body.removeAttribute('data-user-name');
    
    dispatchSessionEvent(false, null);
}

// Listen for multi-tab changes
window.addEventListener('storage', (e) => {
    if (e.key === 'tripmate_session_lost' && e.newValue === 'true') {
        console.log('SessionSync: Session lost in another tab');
        clearAllSessionData();
    } else if (e.key === 'tripmate_active_user_id' && !e.newValue) {
        console.log('SessionSync: User logged out in another tab');
        clearAllSessionData();
    }
});

// Periodic validation
setInterval(() => {
    if (document.body.classList.contains('user-logged-in')) {
        validateSessionWithServer().then(isValid => {
            if (!isValid) {
                console.log('SessionSync: Periodic validation failed');
                clearAllSessionData();
            }
        });
    }
}, 5 * 60 * 1000); // Every 5 minutes
