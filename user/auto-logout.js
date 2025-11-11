/**
 * Auto-Logout System for TripMate
 * File: user/auto-logout.js
 * 
 * Features:
 * - Auto logout after 5 minutes of inactivity
 * - Warning popup 2 minutes before logout
 * - Reset button to restart timer
 * - Logout on browser/tab close
 */

class AutoLogoutManager {
    constructor(options = {}) {
        this.inactivityTimeout = options.inactivityTimeout || 5 * 60 * 1000; // 5 minutes
        this.warningTime = options.warningTime || 2 * 60 * 1000; // 2 minutes before timeout
        this.logoutUrl = options.logoutUrl || '../auth/logout.php';
        
        this.inactivityTimer = null;
        this.warningTimer = null;
        this.countdownInterval = null;
        this.warningShown = false;
        
        this.init();
    }
    
    init() {
        // Only initialize if user is logged in
        if (!this.isUserLoggedIn()) {
            return;
        }
        
        // Create warning modal
        this.createWarningModal();
        
        // Set up activity listeners
        this.setupActivityListeners();
        
        // Set up beforeunload (browser close) handler
        this.setupBeforeUnloadHandler();
        
        // Start the inactivity timer
        this.resetInactivityTimer();
        
        console.log('Auto-logout system initialized');
    }
    
    isUserLoggedIn() {
        // Check if user is logged in (check both storages)
        return !!(localStorage.getItem('tripmate_active_user_id') || 
                  sessionStorage.getItem('user_id'));
    }
    
    createWarningModal() {
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
                    <div class="auto-logout-countdown" id="logoutCountdown">2:00</div>
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
        if (!document.getElementById('autoLogoutModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
        
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
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                if (!this.warningShown) {
                    this.resetInactivityTimer();
                }
            }, true);
        });
    }
    
    setupBeforeUnloadHandler() {
        // Handle browser/tab close
        window.addEventListener('beforeunload', (e) => {
            // Perform logout via sendBeacon (works even when page is unloading)
            const data = new FormData();
            data.append('action', 'logout');
            
            // Use sendBeacon for reliable logout on page unload
            navigator.sendBeacon(this.logoutUrl, data);
            
            // Clear storage
            this.clearUserSession();
        });
        
        // Also handle visibility change (tab switching)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Store the time when user left
                sessionStorage.setItem('lastActiveTime', Date.now().toString());
            } else {
                // Check if too much time has passed
                const lastActive = parseInt(sessionStorage.getItem('lastActiveTime') || '0');
                const elapsed = Date.now() - lastActive;
                
                if (elapsed > this.inactivityTimeout) {
                    // Too much time passed, logout
                    this.performLogout();
                } else {
                    // Reset timer
                    this.resetInactivityTimer();
                }
            }
        });
    }
    
    resetInactivityTimer() {
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
        
        // Set warning timer (shows warning 2 minutes before logout)
        this.warningTimer = setTimeout(() => {
            this.showWarning();
        }, this.inactivityTimeout - this.warningTime);
        
        // Set logout timer
        this.inactivityTimer = setTimeout(() => {
            this.performLogout();
        }, this.inactivityTimeout);
    }
    
    showWarning() {
        this.warningShown = true;
        const modal = document.getElementById('autoLogoutModal');
        modal.style.display = 'flex';
        
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
        modal.style.display = 'none';
        
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    }
    
    updateCountdown(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        const display = `${minutes}:${secs.toString().padStart(2, '0')}`;
        document.getElementById('logoutCountdown').textContent = display;
    }
    
    resetSession() {
        // User clicked "Stay Logged In"
        this.hideWarning();
        this.resetInactivityTimer();
        
        // Show a brief confirmation
        this.showToast('Session extended successfully!', 'success');
    }
    
    performLogout() {
        // Clear storage
        this.clearUserSession();
        
        // Redirect to logout page
        window.location.href = this.logoutUrl;
    }
    
    clearUserSession() {
        // Clear all session data
        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('user_name');
        localStorage.removeItem('tripmate_active_user_id');
        localStorage.removeItem('tripmate_active_user_name');
        sessionStorage.removeItem('lastActiveTime');
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
                animation: slideIn 0.3s ease-out;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }
    
    destroy() {
        // Clean up timers
        if (this.inactivityTimer) clearTimeout(this.inactivityTimer);
        if (this.warningTimer) clearTimeout(this.warningTimer);
        if (this.countdownInterval) clearInterval(this.countdownInterval);
        
        // Remove modal
        const modal = document.getElementById('autoLogoutModal');
        if (modal) modal.remove();
        
        const styles = document.getElementById('autoLogoutStyles');
        if (styles) styles.remove();
    }
}

// Initialize auto-logout system when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.autoLogoutManager = new AutoLogoutManager();
    });
} else {
    window.autoLogoutManager = new AutoLogoutManager();
}
