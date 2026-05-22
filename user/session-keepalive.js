/**
 * Session Keep-Alive System - FIXED PRODUCTION VERSION
 * File: user/session-keepalive.js
 * 
 * FIXES:
 * - Improved session state detection
 * - Better error handling
 * - Fixed race conditions
 * - Prevent unnecessary redirects
 * - Clear logging for debugging
 * 
 * Features:
 * - Periodic session keep-alive pings (every 5 minutes)
 * - Session loss detection
 * - Auto-redirect to login on session expiration
 * - Multi-tab synchronization
 */

class SessionKeepAlive {
    constructor(options = {}) {
        this.baseUrl = this.getBaseUrl();
        this.keepAliveInterval = options.keepAliveInterval || 5 * 60 * 1000; // 5 minutes
        this.sessionCheckInterval = options.sessionCheckInterval || 2 * 60 * 1000; // 2 minutes
        
        this.keepAliveUrl = this.baseUrl + '/user/session_refresh.php';
        this.loginUrl = this.baseUrl + '/auth/login.html';
        
        this.keepAliveTimer = null;
        this.sessionCheckTimer = null;
        this.sessionLostWarning = false;
        this.lastKnownState = null;
        
        // Log initialization
        console.log('🔄 SessionKeepAlive: Constructing...', {
            baseUrl: this.baseUrl,
            keepAliveInterval: this.keepAliveInterval / 1000 + 's',
            sessionCheckInterval: this.sessionCheckInterval / 1000 + 's'
        });
        
        // Check if user is logged in before initializing
        if (this.isUserLoggedIn()) {
            console.log('✅ SessionKeepAlive: User is logged in, initializing');
            this.init();
        } else {
            console.log('ℹ️ SessionKeepAlive: User not logged in, skipping');
        }
    }
    
    /**
     * Check if user is logged in from multiple sources (in order of reliability)
     */
    isUserLoggedIn() {
        // MOST RELIABLE: Check meta tags (PHP-side validation)
        const metaUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (metaUserId && metaUserId !== '' && metaUserId !== 'null') {
            console.log('SessionKeepAlive: User confirmed via meta tag:', metaUserId);
            return true;
        }
        
        // RELIABLE: Check body class
        if (document.body.classList.contains('user-logged-in')) {
            console.log('SessionKeepAlive: User confirmed via body class');
            return true;
        }
        
        // FALLBACK: Check storage (can be out of sync)
        const localId = localStorage.getItem('tripmate_active_user_id');
        const sessionId = sessionStorage.getItem('user_id');
        if (localId || sessionId) {
            console.log('SessionKeepAlive: User found in storage (local:', localId, 'session:', sessionId, ')');
            return true;
        }
        
        console.log('SessionKeepAlive: No user found');
        return false;
    }
    
    /**
     * Get base URL dynamically
     */
    getBaseUrl() {
        // Check for meta tag
        const metaBase = document.querySelector('meta[name="api-base"]')?.getAttribute('content');
        if (metaBase) {
            return metaBase.replace(/\/$/, '');
        }

        // Detect from current URL
        let path = window.location.pathname;
        
        // Remove known app folders
        path = path.replace(/\/(user|auth|main|search|bookings|admin|dashboard|Contributor|database|config|actions|image|uploads|css|js|scripts)(\/.*)?$/, '');
        path = path.replace(/\/$/, '');

        const result = window.location.origin + path;
        console.log('SessionKeepAlive: Detected base URL:', result);
        return result;
    }
    
    /**
     * Initialize the keep-alive system
     */
    init() {
        console.log('🔄 SessionKeepAlive: Initialized');
        console.log('📍 Base URL:', this.baseUrl);
        console.log('🔗 Keep-alive URL:', this.keepAliveUrl);
        
        // Start timers
        this.startKeepAliveTimer();
        this.startSessionCheckTimer();
        
        // Listen for tab visibility changes
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                console.log('SessionKeepAlive: Page became visible, checking session');
                this.checkSessionStatus();
            }
        });
        
        // Listen for storage changes (multi-tab support)
        window.addEventListener('storage', (e) => {
            if (e.key === 'tripmate_session_lost' && e.newValue === 'true') {
                console.log('SessionKeepAlive: Session lost in another tab');
                this.handleSessionLoss();
            }
            else if (e.key === 'tripmate_active_user_id' && !e.newValue) {
                console.log('SessionKeepAlive: User logged out in another tab');
                this.handleSessionLoss();
            }
        });
    }
    
    /**
     * Start the keep-alive timer
     */
    startKeepAliveTimer() {
        // Send initial keep-alive immediately
        this.sendKeepAlive();
        
        this.keepAliveTimer = setInterval(() => {
            if (this.isUserLoggedIn()) {
                this.sendKeepAlive();
            } else {
                console.log('SessionKeepAlive: User not logged in, skipping keep-alive');
            }
        }, this.keepAliveInterval);
        
        console.log('✅ SessionKeepAlive: Keep-alive timer started (interval: ' + this.keepAliveInterval / 1000 + 's)');
    }
    
    /**
     * Send keep-alive ping to server
     */
    async sendKeepAlive() {
        try {
            const url = `${this.keepAliveUrl}?action=keepalive&t=${Date.now()}`;
            console.log('🔄 SessionKeepAlive: Sending keep-alive to', url);
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include', // CRITICAL: Include cookies
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    console.log('✅ SessionKeepAlive: Keep-alive sent successfully');
                    this.sessionLostWarning = false;
                    localStorage.removeItem('tripmate_session_lost');
                    return true;
                } else {
                    console.warn('⚠️ SessionKeepAlive: Server returned error:', data.message);
                    return false;
                }
            } else if (response.status === 401) {
                console.error('❌ SessionKeepAlive: Unauthorized (401) - Session invalid');
                this.handleSessionLoss();
                return false;
            } else {
                console.warn('⚠️ SessionKeepAlive: HTTP ' + response.status);
                return false;
            }
        } catch (error) {
            console.warn('⚠️ SessionKeepAlive: Network error -', error.message);
            // Don't treat network errors as session loss - could be temporary
            return null;
        }
    }
    
    /**
     * Start the session check timer
     */
    startSessionCheckTimer() {
        this.sessionCheckTimer = setInterval(() => {
            this.checkSessionStatus();
        }, this.sessionCheckInterval);
        
        console.log('✅ SessionKeepAlive: Session check timer started (interval: ' + this.sessionCheckInterval / 1000 + 's)');
    }
    
    /**
     * Check if session is still valid
     */
    async checkSessionStatus() {
        try {
            const url = `${this.keepAliveUrl}?action=check&t=${Date.now()}`;
            console.log('🔍 SessionKeepAlive: Checking session status...');
            
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate'
                }
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    console.warn('❌ SessionKeepAlive: Session check failed - unauthorized (401)');
                    this.handleSessionLoss();
                }
                return false;
            }
            
            const data = await response.json();
            
            if (data.success && data.is_valid) {
                console.log('✅ SessionKeepAlive: Session valid');
                this.sessionLostWarning = false;
                localStorage.removeItem('tripmate_session_lost');
                return true;
            } else {
                console.warn('⚠️ SessionKeepAlive: Session validation failed:', data.message);
                if (!this.sessionLostWarning) {
                    this.handleSessionLoss();
                }
                return false;
            }
        } catch (error) {
            console.warn('⚠️ SessionKeepAlive: Check error -', error.message);
            // Don't treat network errors as session loss
            return true;
        }
    }
    
    /**
     * Handle session loss
     */
    handleSessionLoss() {
        if (this.sessionLostWarning) {
            console.log('SessionKeepAlive: Already handling session loss, skipping');
            return; // Already handling
        }
        
        this.sessionLostWarning = true;
        console.error('❌ SessionKeepAlive: Session lost/expired');
        
        // Notify other tabs
        localStorage.setItem('tripmate_session_lost', 'true');
        
        // Clear session data
        this.clearSessionData();
        
        // Show notice
        this.showSessionLostNotice();
        
        // Redirect after delay
        setTimeout(() => {
            if (!this.isUserLoggedIn()) {
                console.log('SessionKeepAlive: Redirecting to login...');
                window.location.href = this.loginUrl;
            }
        }, 3000);
    }
    
    /**
     * Clear all session data
     */
    clearSessionData() {
        console.log('🗑️ SessionKeepAlive: Clearing session data');
        
        localStorage.removeItem('tripmate_active_user_id');
        localStorage.removeItem('tripmate_active_user_name');
        localStorage.removeItem('tripmate_active_user_email');
        localStorage.removeItem('tripmate_active_user_pic');
        
        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('userid');
        sessionStorage.removeItem('user_name');
        sessionStorage.removeItem('username');
        sessionStorage.removeItem('user_email');
        sessionStorage.removeItem('user_pic');
        sessionStorage.removeItem('auth_provider');
        
        document.body.classList.remove('user-logged-in');
        document.body.removeAttribute('data-user-id');
        document.body.removeAttribute('data-user-name');
    }
    
    /**
     * Show session lost notice
     */
    showSessionLostNotice() {
        const existing = document.querySelector('.session-lost-notice');
        if (existing) existing.remove();
        
        const notice = document.createElement('div');
        notice.className = 'session-lost-notice';
        notice.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ef4444;
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
            cursor: pointer;
        `;
        notice.innerHTML = `
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
            Your session has expired. Redirecting to login...
        `;
        notice.onclick = () => {
            window.location.href = this.loginUrl;
        };
        document.body.appendChild(notice);
        
        // Add animation style
        if (!document.querySelector('#session-notice-style')) {
            const style = document.createElement('style');
            style.id = 'session-notice-style';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        setTimeout(() => {
            if (notice.parentNode) notice.remove();
        }, 5000);
    }
    
    /**
     * Stop the keep-alive system
     */
    destroy() {
        if (this.keepAliveTimer) clearInterval(this.keepAliveTimer);
        if (this.sessionCheckTimer) clearInterval(this.sessionCheckTimer);
        console.log('🛑 SessionKeepAlive: Stopped');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('📄 Document loaded, initializing SessionKeepAlive');
    window.sessionKeepAlive = new SessionKeepAlive();
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (window.sessionKeepAlive) {
        window.sessionKeepAlive.destroy();
    }
});
