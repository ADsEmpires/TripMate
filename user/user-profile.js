/**
 * User Profile System for TripMate
 * File: user/user-profile.js
 */

document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for session-sync to run first
    setTimeout(initializeUserProfile, 100);
});

function initializeUserProfile() {
    // Check both class and session storage
    const isLoggedIn = document.body.classList.contains('user-logged-in') || 
                       sessionStorage.getItem('userid') || 
                       sessionStorage.getItem('user_id');

    if (isLoggedIn) {
        // Get user name from session storage
        const userName = sessionStorage.getItem('user_name') || 
                        sessionStorage.getItem('username') || 
                        'User';
        
        // Remove any existing profile elements first
        const existingProfile = document.querySelector('.user-profile');
        if (existingProfile) {
            existingProfile.remove();
        }
        
        // Create user profile element
        const userProfile = document.createElement('div');
        userProfile.className = 'user-profile';
        userProfile.innerHTML = `
            <button class="profile-btn">
                <i class="fas fa-user-circle"></i>
                <span>${userName}</span>
                <i class="fas fa-chevron-down" style="font-size:0.8rem;margin-left:4px"></i>
            </button>
            <div class="profile-dropdown">
                <div class="user-info">
                    <div style="font-size:0.8rem;color:#6b7280">Welcome,</div>
                    <div class="user-name" style="font-weight:700;color:#16034f">${userName}</div>
                </div>
                <div style="border-top:1px solid #eef4ff;margin:8px 0"></div>
                <a href="../user/user_dashboard.php" id="dashboardLink">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="../user/profile_settings.php" id="profileSettingsLink">
                    <i class="fas fa-cog"></i> Profile Settings
                </a>
                <a href="../user/my_bookings.php" id="myBookingsLink">
                    <i class="fas fa-ticket-alt"></i> My Bookings
                </a>
                <div style="border-top:1px solid #eef4ff;margin:8px 0"></div>
                <a href="#" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        `;
        
        // Add to body
        document.body.appendChild(userProfile);
        
        // Toggle dropdown
        const profileBtn = userProfile.querySelector('.profile-btn');
        const dropdown = userProfile.querySelector('.profile-dropdown');
        
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
            
            // Animate chevron
            const chevron = this.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.style.transform = dropdown.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            dropdown.classList.remove('active');
            const chevron = profileBtn.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.style.transform = 'rotate(0)';
            }
        });
        
        // Prevent dropdown from closing when clicking inside it
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Handle logout
        userProfile.querySelector('#logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                // Clear all storage
                sessionStorage.clear();
                localStorage.removeItem('tripmate_active_user_id');
                localStorage.removeItem('tripmate_active_user_name');
                
                // Remove class from body
                document.body.classList.remove('user-logged-in');
                
                // Redirect to logout
                fetch('../auth/logout.php')
                    .then(response => {
                        if (response.ok) {
                            window.location.href = '../main/index.html';
                        }
                    })
                    .catch(error => {
                        console.error('Logout failed:', error);
                        window.location.href = '../main/index.html';
                    });
            }
        });
        
        console.log('User profile initialized for:', userName);
    }
}

// Listen for session sync events
document.addEventListener('sessionSynced', function(e) {
    console.log('Session sync event received:', e.detail);
    if (e.detail.isLoggedIn && !document.querySelector('.user-profile')) {
        initializeUserProfile();
    } else if (!e.detail.isLoggedIn) {
        const profile = document.querySelector('.user-profile');
        if (profile) profile.remove();
    }
});