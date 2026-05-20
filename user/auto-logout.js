/**
 * Auto Logout System
 * File: user/auto-logout.js
 * 
 * Monitors user inactivity and logs out after timeout
 */

class AutoLogout {
    constructor(options = {}) {
        this.inactivityTimeout = options.inactivityTimeout || 2 * 60 * 1000; // 2 minutes
        this.warningTimeout = options.warningTimeout || 1.5 * 60 * 1000; // 1.5 minutes
        this.inactivityTimer = null;
        this.warningShown = false;
        
        if (this.isUserLoggedIn()) {
            this.init();
        }
    }
    
    isUserLoggedIn() {
        return document.body.classList.contains('user-logged-in') ||
               sessionStorage.getItem('user_id') ||
               localStorage.getItem('tripmate_active_user_id');
    }
    
    init() {
        console.log('⏱️ AutoLogout: Initialized (timeout: ' + this.inactivityTimeout / 1000 + 's)');
        this.startInactivityTimer();
        this.attachEventListeners();
    }
    
    startInactivityTimer() {
        this.resetInactivityTimer();
    }
    
    resetInactivityTimer() {
        if (this.inactivityTimer) {
            clearTimeout(this.inactivityTimer);
        }
        
        this.warningShown = false;
        
        this.inactivityTimer = setTimeout(() => {
            if (this.isUserLoggedIn()) {
                console.warn('⏱️ AutoLogout: Inactivity timeout reached');
                this.logout();
            }
        }, this.inactivityTimeout);
    }
    
    attachEventListeners() {
        // Reset timer on user activity
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.addEventListener(event, () => this.resetInactivityTimer(), true);
        });
    }
    
    logout() {
        console.log('🔐 AutoLogout: Logging out due to inactivity');
        
        // Clear session
        sessionStorage.clear();
        localStorage.removeItem('tripmate_active_user_id');
        localStorage.removeItem('tripmate_active_user_name');
        document.body.classList.remove('user-logged-in');
        
        // Notify server
        fetch('../auth/logout.php', { keepalive: true }).catch(() => {});
        
        // Redirect
        window.location.href = '../auth/login.html';
    }
    
    destroy() {
        if (this.inactivityTimer) {
            clearTimeout(this.inactivityTimer);
        }
        console.log('🛑 AutoLogout: Stopped');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.autoLogout = new AutoLogout({
        inactivityTimeout: 2 * 60 * 1000 // 2 minutes
    });
});

// Clean up on unload
window.addEventListener('beforeunload', () => {
    if (window.autoLogout) {
        window.autoLogout.destroy();
    }
});