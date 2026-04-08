<!-- Content wrapper ends here -->
        </div>
    </div>
</main>

<!-- Responsive Footer -->
<footer class="responsive-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Uganda Results System</h3>
                <p>Comprehensive school management and student results tracking system for Uganda educational institutions.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><span>📘</span></a>
                    <a href="#" aria-label="Twitter"><span>🐦</span></a>
                    <a href="#" aria-label="LinkedIn"><span>💼</span></a>
                    <a href="#" aria-label="Email"><span>📧</span></a>
                </div>
            </div>

            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <?php if (!$currentUser): ?>
                        <li><a href="<?php echo base_url('auth/login-admin.php'); ?>">Admin Login</a></li>
                        <li><a href="<?php echo base_url('auth/login-student.php'); ?>">Student Login</a></li>
                        <li><a href="<?php echo base_url('auth/schools.php'); ?>">Schools</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo base_url('admin/'); ?>">Dashboard</a></li>
                        <li><a href="<?php echo base_url('admin/reports.php'); ?>">Reports</a></li>
                        <li><a href="<?php echo base_url('admin/settings.php'); ?>">Settings</a></li>
                    <?php endif; ?>
                    <li><a href="#" onclick="showToast('Help documentation coming soon!', 'info')">Help</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>System Info</h4>
                <ul class="footer-info">
                    <li><strong>Version:</strong> <?php echo APP_VERSION ?? '1.0.0'; ?></li>
                    <li><strong>Last Update:</strong> <?php echo date('M Y'); ?></li>
                    <?php if ($currentUser): ?>
                        <li><strong>Role:</strong> <?php echo ucwords(strtolower(str_replace('_', ' ', $userRole))); ?></li>
                        <li><strong>School:</strong> <?php echo htmlspecialchars($schoolName); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Contact</h4>
                <div class="footer-contact">
                    <p><span>📧</span> support@ugandaresults.edu.ug</p>
                    <p><span>📞</span> +256 XXX XXX XXX</p>
                    <p><span>🏢</span> Ministry of Education<br>Uganda</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-copyright">
                <p>&copy; <?php echo date('Y'); ?> Uganda Results System by <strong>Aturinda Ronald</strong>. All rights reserved.</p>
            </div>
            <div class="footer-tools">
                <button id="scroll-to-top" class="scroll-top-btn" aria-label="Scroll to top">⬆️</button>
                <button id="toggle-dark-mode" class="dark-mode-toggle" aria-label="Toggle dark mode">🌙</button>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile Bottom Navigation (for logged-in users) -->
<?php if ($currentUser): ?>
<nav class="mobile-bottom-nav">
    <a href="<?php echo base_url(($userRole === 'STUDENT') ? 'student/' : 'admin/'); ?>" class="bottom-nav-item">
        <span class="bottom-nav-icon">📋</span>
        <span class="bottom-nav-text">Dashboard</span>
    </a>

    <?php if ($userRole !== 'STUDENT'): ?>
        <a href="<?php echo base_url('admin/students.php'); ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">🎓</span>
            <span class="bottom-nav-text">Students</span>
        </a>
        <a href="<?php echo base_url('admin/results.php'); ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">📝</span>
            <span class="bottom-nav-text">Results</span>
        </a>
        <a href="<?php echo base_url('admin/reports.php'); ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">📊</span>
            <span class="bottom-nav-text">Reports</span>
        </a>
    <?php else: ?>
        <a href="<?php echo base_url('student/results.php'); ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">📝</span>
            <span class="bottom-nav-text">Results</span>
        </a>
        <a href="<?php echo base_url('student/reports.php'); ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">📄</span>
            <span class="bottom-nav-text">Reports</span>
        </a>
        <a href="<?php echo base_url('student/profile.php'); ?>" class="bottom-nav-item">
            <span class="bottom-nav-icon">👤</span>
            <span class="bottom-nav-text">Profile</span>
        </a>
    <?php endif; ?>

    <a href="<?php echo base_url('auth/logout.php'); ?>" class="bottom-nav-item">
        <span class="bottom-nav-icon">🚪</span>
        <span class="bottom-nav-text">Logout</span>
    </a>
</nav>
<?php endif; ?>

<!-- JavaScript -->
<script src="<?php echo asset_url('js/mobile-responsive.js'); ?>"></script>

<!-- Additional page-specific JavaScript -->
<?php if (isset($additionalJS)): ?>
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?php echo asset_url($js); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Inline JavaScript for footer functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to top functionality
    const scrollTopBtn = document.getElementById('scroll-to-top');
    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Show/hide scroll to top button
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollTopBtn.style.display = 'flex';
            } else {
                scrollTopBtn.style.display = 'none';
            }
        });
    }

    // Dark mode toggle (basic implementation)
    const darkModeToggle = document.getElementById('toggle-dark-mode');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark);
            this.textContent = isDark ? '☀️' : '🌙';
            showToast(isDark ? 'Dark mode enabled' : 'Light mode enabled', 'info', 1500);
        });

        // Load dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkModeToggle.textContent = '☀️';
        }
    }

    // Auto-hide flash messages
    const flashMessage = document.getElementById('flash-message');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.transition = 'opacity 0.5s ease';
            flashMessage.style.opacity = '0';
            setTimeout(() => flashMessage.remove(), 500);
        }, 5000);
    }

    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.innerHTML = '<span class="loading"></span> ' + submitBtn.textContent;
                submitBtn.disabled = true;
            }
        });
    });

    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn, button');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');

            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
});

// PWA install prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    // Show custom install button
    const installBtn = document.createElement('button');
    installBtn.textContent = '📱 Install App';
    installBtn.className = 'btn btn-primary install-btn';
    installBtn.style.position = 'fixed';
    installBtn.style.bottom = '20px';
    installBtn.style.right = '20px';
    installBtn.style.zIndex = '1000';

    installBtn.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                showToast('App installed successfully!', 'success');
            }
            deferredPrompt = null;
            installBtn.remove();
        }
    });

    document.body.appendChild(installBtn);

    // Auto-hide after 10 seconds
    setTimeout(() => {
        if (installBtn.parentNode) {
            installBtn.remove();
        }
    }, 10000);
});
</script>

<!-- Footer Styles -->
<style>
.responsive-footer {
    background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
    color: white;
    margin-top: 3rem;
    padding: 2rem 0 1rem;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-section h3,
.footer-section h4 {
    margin-bottom: 1rem;
    color: white;
}

.footer-section h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.footer-section h4 {
    font-size: 1.1rem;
    color: #ecf0f1;
}

.footer-section p {
    line-height: 1.6;
    opacity: 0.9;
}

.footer-links,
.footer-info {
    list-style: none;
}

.footer-links li,
.footer-info li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: #bdc3c7;
    text-decoration: none;
    transition: var(--transition);
}

.footer-links a:hover {
    color: white;
    padding-left: 5px;
}

.footer-social {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.footer-social a {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: var(--transition);
}

.footer-social a:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.footer-contact p {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 2rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-tools {
    display: flex;
    gap: 0.5rem;
}

.scroll-top-btn,
.dark-mode-toggle {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    color: white;
    cursor: pointer;
    transition: var(--transition);
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.scroll-top-btn:hover,
.dark-mode-toggle:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.dark-mode-toggle {
    display: flex;
}

/* Mobile Bottom Navigation */
.mobile-bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid var(--border-color);
    padding: 0.5rem 0;
    z-index: 1000;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
}

.bottom-nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: var(--text-color);
    padding: 0.5rem;
    transition: var(--transition);
    border-radius: var(--border-radius);
    margin: 0 0.25rem;
}

.bottom-nav-item:hover,
.bottom-nav-item.active {
    background: rgba(52, 152, 219, 0.1);
    color: var(--secondary-color);
}

.bottom-nav-icon {
    font-size: 1.2rem;
    margin-bottom: 0.25rem;
}

.bottom-nav-text {
    font-size: 0.7rem;
    font-weight: 500;
}

/* Dark mode styles */
.dark-mode {
    background: #1a1a1a;
    color: #e0e0e0;
}

.dark-mode .content-wrapper {
    background: #2d2d2d;
    color: #e0e0e0;
}

.dark-mode .form-control {
    background: #3d3d3d;
    border-color: #555;
    color: #e0e0e0;
}

.dark-mode .responsive-table {
    background: #2d2d2d;
}

.dark-mode .card {
    background: #2d2d2d;
    color: #e0e0e0;
}

/* Ripple effect */
.ripple {
    position: absolute;
    border-radius: 50%;
    transform: scale(0);
    animation: ripple-animation 0.6s linear;
    background-color: rgba(255, 255, 255, 0.6);
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .mobile-bottom-nav {
        display: flex;
        padding-bottom: env(safe-area-inset-bottom);
    }

    .main-content {
        padding-bottom: 70px; /* Space for bottom nav */
    }

    .footer-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }

    .footer-section {
        text-align: center;
    }

    .footer-social {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .responsive-footer {
        padding: 1.5rem 0 1rem;
    }

    .footer-content {
        gap: 1rem;
    }

    .footer-section h3 {
        font-size: 1.2rem;
    }

    .footer-section h4 {
        font-size: 1rem;
    }
}

/* Install button styles */
.install-btn {
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>

</body>
</html>
