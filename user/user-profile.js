/**
 * User Profile System for TripMate
 * File: user/user-profile.js
 * 
 * Features:
 * - Dynamic profile menu creation
 * - Session management
 * - Logout functionality
 * - Profile settings integration
 */

document.addEventListener('DOMContentLoaded', function() {
    // Wait for session-sync to complete
    setTimeout(initializeUserProfile, 150);
});

function initializeUserProfile() {
    // Check if user is logged in via multiple methods
    const isLoggedIn = document.body.classList.contains('user-logged-in') || 
                       sessionStorage.getItem('userid') || 
                       sessionStorage.getItem('user_id') ||
                       localStorage.getItem('tripmate_active_user_id');
    
    // Also check for meta tags from PHP
    const metaUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    const metaUserName = document.querySelector('meta[name="user-name"]')?.getAttribute('content');
    
    if (isLoggedIn || metaUserId) {
        // Get user name from various sources
        const userName = metaUserName || 
                        sessionStorage.getItem('user_name') || 
                        sessionStorage.getItem('username') || 
                        localStorage.getItem('tripmate_active_user_name') || 
                        'User';
        
        const userId = metaUserId || 
                      sessionStorage.getItem('user_id') || 
                      sessionStorage.getItem('userid') || 
                      localStorage.getItem('tripmate_active_user_id');
        
        // Update session storage to ensure consistency
        if (userId) {
            sessionStorage.setItem('user_id', userId);
            sessionStorage.setItem('userid', userId);
            sessionStorage.setItem('user_name', userName);
            sessionStorage.setItem('username', userName);
            localStorage.setItem('tripmate_active_user_id', userId);
            localStorage.setItem('tripmate_active_user_name', userName);
            document.body.classList.add('user-logged-in');
        }
        
        // Remove any existing profile element to avoid duplicates
        const existingProfile = document.querySelector('.user-profile');
        if (existingProfile) {
            existingProfile.remove();
        }
        
        // Only create profile if we're on a page that doesn't already have navbar profile
        const existingNavProfile = document.querySelector('.navbar .profile-menu');
        if (!existingNavProfile) {
            createFloatingProfile(userName, userId);
        }
        
        console.log('User profile initialized for:', userName);
    }
}

function createFloatingProfile(userName, userId) {
    // Create floating profile element (for pages without navbar profile)
    const userProfile = document.createElement('div');
    userProfile.className = 'user-profile';
    userProfile.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 1000;';
    userProfile.innerHTML = `
        <button class="profile-btn" style="background: linear-gradient(135deg, #E55437, #C43A1F); border: none; padding: 8px 20px; border-radius: 30px; color: white; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-user-circle" style="font-size: 1.4rem;"></i>
            <span>${escapeHtml(userName)}</span>
            <i class="fas fa-chevron-down" style="font-size: 0.8rem;"></i>
        </button>
        <div class="profile-dropdown" style="position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.15); margin-top: 12px; overflow: hidden; display: none; width: 240px;">
            <div class="profile-header" style="padding: 16px; background: linear-gradient(135deg, #E55437, #C43A1F); color: white;">
                <div class="font-bold">${escapeHtml(userName)}</div>
                <div class="text-sm opacity-80">Traveler</div>
            </div>
            <a href="../user/user_dashboard.php" style="display: flex; align-items: center; padding: 12px 16px; text-decoration: none; color: #374151; gap: 12px;">
                <i class="fas fa-tachometer-alt" style="color: #E55437; width: 20px;"></i> Dashboard
            </a>
            <a href="../user/user_profile.php" style="display: flex; align-items: center; padding: 12px 16px; text-decoration: none; color: #374151; gap: 12px;">
                <i class="fas fa-user" style="color: #E55437; width: 20px;"></i> My Profile
            </a>
            <a href="../user/favourites.php" style="display: flex; align-items: center; padding: 12px 16px; text-decoration: none; color: #374151; gap: 12px;">
                <i class="fas fa-heart" style="color: #E55437; width: 20px;"></i> Favorites
            </a>
            <hr style="margin: 8px 0; border: none; border-top: 1px solid #eef4ff;">
            <button id="logoutBtnFloating" style="display: flex; align-items: center; padding: 12px 16px; width: 100%; text-align: left; border: none; background: none; cursor: pointer; color: #ef4444; gap: 12px;">
                <i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout
            </button>
        </div>
    `;
    
    document.body.appendChild(userProfile);
    
    // Toggle dropdown
    const profileBtn = userProfile.querySelector('.profile-btn');
    const dropdown = userProfile.querySelector('.profile-dropdown');
    
    profileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        const chevron = this.querySelector('.fa-chevron-down');
        if (chevron) {
            chevron.style.transform = dropdown.style.display === 'block' ? 'rotate(180deg)' : 'rotate(0)';
        }
    });
    
    document.addEventListener('click', function() {
        dropdown.style.display = 'none';
        const chevron = profileBtn.querySelector('.fa-chevron-down');
        if (chevron) chevron.style.transform = 'rotate(0)';
    });
    
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Handle logout
    userProfile.querySelector('#logoutBtnFloating').addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            performLogout();
        }
    });
}

function performLogout() {
    // Clear all storage
    sessionStorage.clear();
    localStorage.removeItem('tripmate_active_user_id');
    localStorage.removeItem('tripmate_active_user_name');
    localStorage.removeItem('tripmate-theme');
    
    // Remove class from body
    document.body.classList.remove('user-logged-in');
    
    // Send logout request
    fetch('../auth/logout.php', { method: 'POST', keepalive: true })
        .catch(console.error);
    
    // Redirect to home
    window.location.href = '../main/index.html';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Listen for session sync events
document.addEventListener('sessionSynced', function(e) {
    console.log('Session sync event received:', e.detail);
    if (e.detail.isLoggedIn && !document.querySelector('.navbar .profile-menu') && !document.querySelector('.user-profile')) {
        initializeUserProfile();
    }
});