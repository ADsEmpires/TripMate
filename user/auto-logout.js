/**
 * Auto-Logout System for TripMate
 * File: user/auto-logout.js
 * 
 * Features:
 * - Auto logout after 30 minutes of inactivity
 * - Server-side session validation
 * - Warning popup 2 minutes before logout
 * - Reset button to restart timer
 * - Logout on browser/tab close
 * - Manual logout by user
 * - Periodic session validation with server
 */

class AutoLogoutManager {
    constructor(options = {}) {
        this.inactivityTimeout = options.inactivityTimeout || 30 * 60 * 1000; // 30 minutes
        this.warningTime = options.warningTime || 2 * 60 * 1000; // 2 minutes before timeout
        this.logoutUrl = options.logoutUrl || '../auth/logout.php';
        this.validateSessionUrl = options.validateSessionUrl || '../auth/session_config.php';
        
        this.inactivityTimer = null;
        this.warningTimer = null;
        this.countdownInterval = null;
        this.warningShown = false;
        this.sessionValidationInterval = null;
        
        this.init();
    }
    
    init() {
        // Only initialize if user is logged in
        if (!this.isUserLoggedIn()) {
            console.log('Auto-logout: User not logged in, skipping initialization');
            return;
        }
        
        console.log('Auto-logout system initialized for user:', this.getUserName());
        
        // Create warning modal
        this.createWarningModal();
        
        // Set up activity listeners
        this.setupActivityListeners();
        
        // Start the inactivity timer
        this.resetInactivityTimer();
        
        // Start periodic server-side session validation (every 5 minutes)
        this.startSessionValidation();
        
        // Set up beforeunload event for browser/tab close
        this.setupUnloadListener();
    }
    
    isUserLoggedIn() {
        // Check if user is logged in using the main session keys
        const hasSession = !!(sessionStorage.getItem('user_id') || localStorage.getItem('tripmate_active_user_id'));
        console.log('Auto-logout: Checking login status -', hasSession);
        return hasSession;
    }
    
    getUserName() {
        return sessionStorage.getItem('user_name') || localStorage.getItem('tripmate_active_user_name') || 'User';
    }
    
    /**
     * Start periodic validation of session on server
     */
    startSessionValidation() {
        // Validate every 5 minutes
        this.sessionValidationInterval = setInterval(() => {
            this.validateSessionWithServer();
        }, 5 * 60 * 1000);
    }
    
    /**
     * Validate session state with server
     */
    validateSessionWithServer() {
        if (!this.isUserLoggedIn()) {
            if (this.sessionValidationInterval) {
                clearInterval(this.sessionValidationInterval);
            }
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'validate_session');
        
        fetch(this.validateSessionUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.status === 401) {
                console.warn('Auto-logout: Server session expired');
                this.performLogout();
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (data && !data.valid) {
                console.warn('Auto-logout: Server session invalid -', data.reason);
                this.performLogout();
            } else if (data && data.warning) {
                console.warn('Auto-logout: Session expiring soon', data.time_remaining, 'seconds');
                if (!this.warningShown) {
                    this.showWarning();
                }
            }
        })
        .catch(error => {
            console.error('Auto-logout: Error validating session', error);
        });
    }
    
    setupUnloadListener() {
        // Track whether the user is navigating to another page (NOT closing the tab).
        // Safari fires 'beforeunload' on EVERY navigation (link clicks, form submits),
        // so we must NOT send a logout beacon during normal page transitions.
        this._isNavigatingAway = false;

        // Mark navigations triggered by links and form submissions
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (link && !link.getAttribute('href').startsWith('#') && !link.getAttribute('href').startsWith('javascript')) {
                this._isNavigatingAway = true;
            }
        }, true);

        document.addEventListener('submit', () => {
            this._isNavigatingAway = true;
        }, true);

        // Also mark programmatic navigations (window.location changes)
        const self = this;
        const origAssign = window.location.assign;
        const origReplace = window.location.replace;
        if (origAssign) {
            window.location.assign = function() {
                self._isNavigatingAway = true;
                return origAssign.apply(this, arguments);
            };
        }
        if (origReplace) {
            window.location.replace = function() {
                self._isNavigatingAway = true;
                return origReplace.apply(this, arguments);
            };
        }

        // Only send logout beacon when the tab/browser is truly closing,
        // NOT during normal page navigation.
        window.addEventListener('beforeunload', () => {
            if (this._isNavigatingAway) {
                // User is navigating to another page — do NOT logout
                return;
            }
            if (this.isUserLoggedIn() && navigator.sendBeacon) {
                const formData = new FormData();
                formData.append('action', 'logout');
                navigator.sendBeacon(this.logoutUrl, formData);
            }
        });
    }
    
    createWarningModal() {
        // Only create modal if it doesn't exist
        if (document.getElementById('autoLogoutModal')) {
            return;
        }
        
        // Create modal HTML
        const modalHTML = `
            <div id="autoLogoutModal" class="auto-logout-modal" style="display: none;">
                <div class="auto-logout-overlay"></div>
                <div class="auto-logout-content">
                    <div class="auto-logout-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2>Session Timeout Warning</h2>
                    <p>You will be logged out due to inactivity in:</p>
                    <div class="auto-logout-countdown" id="logoutCountdown">1:00</div>
                    <p class="auto-logout-message">Click "Stay Logged In" to continue your session.</p>
                    <div class="auto-logout-buttons">
                        <button class="auto-logout-btn auto-logout-btn-primary" id="stayLoggedInBtn">
                            <i class="fas fa-sync-alt"></i> Stay Logged In
                        </button>
                        <button class="auto-logout-btn auto-logout-btn-secondary" id="logoutNowBtn">
                            <i class="fas fa-sign-out-alt"></i> Logout Now
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Create styles
        const styles = `
            <style id="autoLogoutStyles">
                .auto-logout-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .auto-logout-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.6);
                    backdrop-filter: blur(4px);
                }
                
                .auto-logout-content {
                    position: relative;
                    background: white;
                    border-radius: 16px;
                    padding: 32px;
                    max-width: 450px;
                    width: 90%;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    text-align: center;
                    animation: slideDown 0.3s ease-out;
                }
                
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .auto-logout-icon {
                    font-size: 64px;
                    color: #ff9800;
                    margin-bottom: 16px;
                    animation: pulse 2s infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
                
                .auto-logout-content h2 {
                    margin: 0 0 16px 0;
                    color: #16034f;
                    font-size: 24px;
                    font-weight: 700;
                }
                
                .auto-logout-content p {
                    color: #6b7280;
                    margin: 8px 0;
                    font-size: 16px;
                }
                
                .auto-logout-countdown {
                    font-size: 48px;
                    font-weight: 800;
                    color: #ef4444;
                    margin: 24px 0;
                    font-family: 'Courier New', monospace;
                    text-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
                }
                
                .auto-logout-message {
                    font-size: 14px;
                    margin-bottom: 24px;
                }
                
                .auto-logout-buttons {
                    display: flex;
                    gap: 12px;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                
                .auto-logout-btn {
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .auto-logout-btn-primary {
                    background: #16034f;
                    color: white;
                }
                
                .auto-logout-btn-primary:hover {
                    background: #2a0a8a;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(22, 3, 79, 0.3);
                }
                
                .auto-logout-btn-secondary {
                    background: #f3f4f6;
                    color: #374151;
                }
                
                .auto-logout-btn-secondary:hover {
                    background: #e5e7eb;
                }
                
                @media (max-width: 480px) {
                    .auto-logout-content {
                        padding: 24px;
                    }
                    
                    .auto-logout-countdown {
                        font-size: 36px;
                    }
                    
                    .auto-logout-buttons {
                        flex-direction: column;
                    }
                    
                    .auto-logout-btn {
                        width: 100%;
                        justify-content: center;
                    }
                }
            </style>
        `;
        
        // Add to document
        if (!document.getElementById('autoLogoutStyles')) {
            document.head.insertAdjacentHTML('beforeend', styles);
        }
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Add event listeners
        document.getElementById('stayLoggedInBtn').addEventListener('click', () => {
            this.resetSession();
        });
        
        document.getElementById('logoutNowBtn').addEventListener('click', () => {
            this.performLogout();
        });
    }
    
    setupActivityListeners() {
        // List of events that indicate user activity
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click', 'keydown'];
        
        const activityHandler = () => {
            if (!this.warningShown) {
                this.resetInactivityTimer();
            }
        };
        
        events.forEach(event => {
            document.addEventListener(event, activityHandler, { passive: true });
        });
        
        // Also track page visibility
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !this.warningShown) {
                this.resetInactivityTimer();
            }
        });
    }
    
    resetInactivityTimer() {
        console.log('Auto-logout: Resetting inactivity timer');
        
        // Clear existing timers
        if (this.inactivityTimer) {
            clearTimeout(this.inactivityTimer);
        }
        if (this.warningTimer) {
            clearTimeout(this.warningTimer);
        }
        
        // Hide warning if shown
        if (this.warningShown) {
            this.hideWarning();
        }
        
        // Refresh session on server
        this.refreshSessionOnServer();
        
        // Set warning timer (shows warning 2 minutes before logout)
        this.warningTimer = setTimeout(() => {
            this.showWarning();
        }, this.inactivityTimeout - this.warningTime);
        
        // Set logout timer
        this.inactivityTimer = setTimeout(() => {
            this.performLogout();
        }, this.inactivityTimeout);
    }
    
    /**
     * Refresh session activity on server
     */
    refreshSessionOnServer() {
        const formData = new FormData();
        formData.append('action', 'refresh_session');
        
        fetch(this.validateSessionUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .catch(error => {
            console.warn('Auto-logout: Could not refresh session on server', error);
        });
    }
    
    showWarning() {
        console.log('Auto-logout: Showing warning modal');
        this.warningShown = true;
        const modal = document.getElementById('autoLogoutModal');
        if (modal) {
            modal.style.display = 'flex';
        }
        
        // Start countdown
        let remainingSeconds = Math.floor(this.warningTime / 1000);
        this.updateCountdown(remainingSeconds);
        
        this.countdownInterval = setInterval(() => {
            remainingSeconds--;
            if (remainingSeconds <= 0) {
                clearInterval(this.countdownInterval);
                this.performLogout();
            } else {
                this.updateCountdown(remainingSeconds);
            }
        }, 1000);
    }
    
    hideWarning() {
        this.warningShown = false;
        const modal = document.getElementById('autoLogoutModal');
        if (modal) {
            modal.style.display = 'none';
        }
        
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    }
    
    updateCountdown(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        const display = `${minutes}:${secs.toString().padStart(2, '0')}`;
        const countdownEl = document.getElementById('logoutCountdown');
        if (countdownEl) {
            countdownEl.textContent = display;
        }
    }
    
    resetSession() {
        // User clicked "Stay Logged In"
        console.log('Auto-logout: User reset session');
        this.hideWarning();
        this.resetInactivityTimer();
        
        // Show a brief confirmation
        this.showToast('Session extended successfully!', 'success');
    }
    
    performLogout() {
        console.log('Auto-logout: Performing logout');
        
        // Clear storage
        this.clearUserSession();
        
        // Use Beacon API for reliable logout even during page unload
        if (navigator.sendBeacon) {
            const formData = new FormData();
            formData.append('action', 'logout');
            navigator.sendBeacon(this.logoutUrl, formData);
        }
        
        // Redirect to logout page
        window.location.href = this.logoutUrl;
    }
    
    clearUserSession() {
        console.log('Auto-logout: Clearing user session');
        
        // Clear all session data
        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('user_name');
        sessionStorage.removeItem('userid');
        sessionStorage.removeItem('username');
        localStorage.removeItem('tripmate_active_user_id');
        localStorage.removeItem('tripmate_active_user_name');
        sessionStorage.removeItem('lastActiveTime');
        
        // Remove user-logged-in class from body
        document.body.classList.remove('user-logged-in');
    }
    
    showToast(message, type = 'info') {
        // Use existing toast function if available
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            // Fallback simple notification
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : '#3b82f6'};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10001;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    }
    
    destroy() {
        // Clean up timers
        if (this.inactivityTimer) clearTimeout(this.inactivityTimer);
        if (this.warningTimer) clearTimeout(this.warningTimer);
        if (this.countdownInterval) clearInterval(this.countdownInterval);
        if (this.sessionValidationInterval) clearInterval(this.sessionValidationInterval);
        
        // Remove event listeners
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click', 'keydown'];
        events.forEach(event => {
            document.removeEventListener(event, this.activityHandler);
        });
        
        // Remove beforeunload listener
        window.removeEventListener('beforeunload', this.beforeUnloadHandler);
    }
}

// Initialize auto-logout system when DOM is ready
// Only initialize if user is actually logged in
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in using the same method as user-profile.js
    const isLoggedIn = document.body.classList.contains('user-logged-in') || 
                      sessionStorage.getItem('userid') || 
                      sessionStorage.getItem('user_id') ||
                      localStorage.getItem('tripmate_active_user_id');
    
    if (isLoggedIn) {
        console.log('Initializing auto-logout system for logged-in user');
        window.autoLogoutManager = new AutoLogoutManager();
    } else {
        console.log('Skipping auto-logout initialization - user not logged in');
    }
});