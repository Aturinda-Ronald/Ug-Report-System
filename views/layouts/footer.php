<!-- JavaScript -->
    <script src="<?php echo asset_url('js/app.js'); ?>"></script>

    <!-- Dashboard JavaScript -->
    <?php if (is_logged_in()): ?>
    <script>
        // Dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const mainContent = document.getElementById('mainContent');
            const twoFactorToggle = document.getElementById('twoFactorToggle');

            // Mobile menu toggle
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    sidebar.classList.remove('show');
                }
            });

            // 2FA Toggle functionality
            if (twoFactorToggle) {
                twoFactorToggle.addEventListener('click', function() {
                    this.classList.toggle('active');
                    // Add your 2FA toggle logic here
                    const isActive = this.classList.contains('active');
                    console.log('2FA Status:', isActive ? 'ON' : 'OFF');
                });
            }

            // Active navigation highlighting
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');

            navLinks.forEach(link => {
                const linkPath = new URL(link.href).pathname;
                if (currentPath === linkPath || currentPath.includes(linkPath.split('/')[1])) {
                    link.classList.add('active');
                }
            });

            // Smooth scrolling for internal links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Loading overlay functions
            window.showLoading = function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.display = 'flex';
                }
            };

            window.hideLoading = function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.display = 'none';
                }
            };

            // Auto-hide loading after page load
            window.addEventListener('load', function() {
                setTimeout(hideLoading, 500);
            });
        });
    </script>
    <?php endif; ?>

    <?php if (isset($additionalJs)): ?>
        <?php foreach ($additionalJs as $jsFile): ?>
            <script src="<?php echo asset_url($jsFile); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Page-specific JavaScript -->
    <?php if (isset($pageJs)): ?>
        <script><?php echo $pageJs; ?></script>
    <?php endif; ?>
</body>
</html>
