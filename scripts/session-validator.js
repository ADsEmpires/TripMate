/**
 * Session Validator for Frontend
 * File: scripts/session-validator.js
 * 
 * This script validates user session on page load and periodically
 * Ensures user is logged in and session hasn't expired
 */

class SessionValidator {
    constructor(options = {}) {
        this.checkUrl = options.checkUrl || '../auth/session_config.php';
        this.loginUrl = options.loginUrl || '../auth/login.html';
        this.checkInterval = options.checkInterval || 5 * 60 * 1000; // 5 minutes
        this.validationTimer = null;
    }
    
    init() {
        // Validate session on page load
        this.validateSession();
        
        // Set up periodic validation
        this.validationTimer = setInterval(() => {
            this.validateSession();
        }, this.checkInterval);
        
        // Validate when page becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                console.log('SessionValidator: Page became visible, validating session');
                this.validateSession();
            }
        });
    }
    
    validateSession() {
        // Check if user is logged in via frontend storage
        const userId = sessionStorage.getItem('user_id') || localStorage.getItem('tripmate_active_user_id');
        
        if (!userId) {
            console.log('SessionValidator: No user logged in');
            return;
        }
        
        // Validate with server
        const formData = new FormData();
        formData.append('action', 'validate_session');
        
        fetch(this.checkUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.status === 401) {
                console.warn('SessionValidator: Session expired on server');
                this.handleSessionExpired();
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (data && !data.valid) {
                console.warn('SessionValidator: Server session invalid');
                this.handleSessionExpired();
            }
        })
        .catch(error => {
            console.error('SessionValidator: Error validating session', error);
        });
    }
    
    handleSessionExpired() {
        // Clear frontend storage
        localStorage.removeItem('tripmate_active_user_id');
        localStorage.removeItem('tripmate_active_user_name');
        localStorage.removeItem('tripmate_session_start');
        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('user_name');
        sessionStorage.removeItem('auth_provider');
        sessionStorage.removeItem('user_email');
        sessionStorage.removeItem('user_pic');
        
        // Clear validation timer
        if (this.validationTimer) {
            clearInterval(this.validationTimer);
        }
        
        // Redirect to login
        window.location.href = this.loginUrl;
    }
    
    destroy() {
        if (this.validationTimer) {
            clearInterval(this.validationTimer);
        }
    }
}

// Initialize validator when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const validator = new SessionValidator();
        validator.init();
        window.sessionValidator = validator;
    });
} else {
    const validator = new SessionValidator();
    validator.init();
    window.sessionValidator = validator;
}
