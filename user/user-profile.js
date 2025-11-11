document.addEventListener('DOMContentLoaded', function() {
    // Check both class and session storage
    const isLoggedIn = document.body.classList.contains('user-logged-in') && sessionStorage.getItem('userid');

    if (isLoggedIn) {
        // Get user name from session storage
        const userName = sessionStorage.getItem('user_name') || sessionStorage.getItem('username');
        
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
                <i class="fas fa-user"></i>
                <span>${userName}</span>
            </button>
            <div class="profile-dropdown">
                <div class="user-info">
                    Welcome, <span class="user-name">${userName}</span>
                </div>
                <a href="../user/user_dashboard.php" id="dashboardLink">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
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
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            dropdown.classList.remove('active');
        });
        
        // Handle logout
        userProfile.querySelector('#logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                sessionStorage.clear();
                fetch('../auth/logout.php', {
                    method: 'POST',
                    credentials: 'same-origin', // include session cookie for same-origin
                    headers: { 'Accept': 'application/json' }
                })
                .then(response => {
                    if (response.ok) {
                        // optional: check JSON for success
                        window.location.href = '../main/index.html';
                    } else {
                        console.error('Logout failed, server returned', response.status);
                    }
                })
                .catch(error => console.error('Logout failed:', error));
            }
        });
    }
});