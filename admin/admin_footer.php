        </div>
    </div>

    <script>
        // Dropdown toggle JS
        document.addEventListener('DOMContentLoaded', function() {
            const userProfile = document.getElementById('userProfile');
            const dropdown = document.getElementById('profileDropdown');

            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                userProfile.classList.toggle('active');
                dropdown.classList.toggle('active');
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target)) {
                    userProfile.classList.remove('active');
                    dropdown.classList.remove('active');
                }
            });

            // Close on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    userProfile.classList.remove('active');
                    dropdown.classList.remove('active');
                }
            });

            // Add active class to current menu item
            const currentPage = '<?= basename($_SERVER['PHP_SELF']) ?>';
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const link = item.querySelector('a');
                if (link && link.getAttribute('href') === currentPage) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>