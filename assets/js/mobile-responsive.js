/**
 * Uganda Results System - Mobile Responsive JavaScript
 * Handles hamburger menu, mobile navigation, and responsive features
 */

class MobileResponsive {
    constructor() {
        this.init();
    }

    init() {
        this.setupHamburgerMenu();
        this.setupMobileNavigation();
        this.setupResponsiveTables();
        this.setupTouchFriendly();
        this.setupResponsiveForms();
        this.setupToasts();

        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.onDOMReady();
            });
        } else {
            this.onDOMReady();
        }
    }

    onDOMReady() {
        this.addViewportMeta();
        this.enhanceExistingElements();
        this.setupSwipeGestures();
    }

    /**
     * Add viewport meta tag if not present
     */
    addViewportMeta() {
        if (!document.querySelector('meta[name="viewport"]')) {
            const viewport = document.createElement('meta');
            viewport.name = 'viewport';
            viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
            document.head.appendChild(viewport);
        }
    }

    /**
     * Setup hamburger menu functionality
     */
    setupHamburgerMenu() {
        // Create hamburger button if not exists
        const header = document.querySelector('.mobile-header') || document.querySelector('header') || document.body;

        if (!document.querySelector('.hamburger')) {
            const hamburger = this.createHamburgerButton();
            header.appendChild(hamburger);
        }

        // Setup click handlers
        document.addEventListener('click', (e) => {
            const hamburger = e.target.closest('.hamburger');
            if (hamburger) {
                this.toggleMobileMenu();
            }

            const overlay = e.target.closest('.nav-overlay');
            if (overlay) {
                this.closeMobileMenu();
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeMobileMenu();
            }
        });
    }

    /**
     * Create hamburger button element
     */
    createHamburgerButton() {
        const button = document.createElement('button');
        button.className = 'hamburger';
        button.setAttribute('aria-label', 'Toggle menu');
        button.innerHTML = `
            <span></span>
            <span></span>
            <span></span>
        `;
        return button;
    }

    /**
     * Setup mobile navigation
     */
    setupMobileNavigation() {
        // Create mobile nav if not exists
        if (!document.querySelector('.mobile-nav')) {
            this.createMobileNavigation();
        }

        // Create overlay if not exists
        if (!document.querySelector('.nav-overlay')) {
            this.createNavOverlay();
        }
    }

    /**
     * Create mobile navigation menu
     */
    createMobileNavigation() {
        // Find existing navigation items
        const existingNav = document.querySelector('nav ul') || document.querySelector('.desktop-nav');
        const navItems = [];

        if (existingNav) {
            const links = existingNav.querySelectorAll('a');
            links.forEach(link => {
                navItems.push({
                    text: link.textContent.trim(),
                    href: link.href,
                    icon: this.getIconForNavItem(link.textContent.trim())
                });
            });
        } else {
            // Default navigation items for Uganda Results System
            navItems.push(
                { text: 'Home', href: './', icon: '🏠' },
                { text: 'Admin Login', href: './auth/login-admin.php', icon: '👨‍💼' },
                { text: 'Student Login', href: './auth/login-student.php', icon: '🎓' },
                { text: 'Schools', href: './auth/schools.php', icon: '🏫' },
                { text: 'Reports', href: './admin/reports.php', icon: '📊' },
                { text: 'Students', href: './admin/students.php', icon: '👥' },
                { text: 'Settings', href: './admin/settings.php', icon: '⚙️' }
            );
        }

        const mobileNav = document.createElement('nav');
        mobileNav.className = 'mobile-nav';

        const navList = document.createElement('ul');
        navList.className = 'mobile-nav-list';

        navItems.forEach(item => {
            const listItem = document.createElement('li');
            listItem.innerHTML = `
                <a href="${item.href}">
                    <span class="nav-icon">${item.icon}</span>
                    ${item.text}
                </a>
            `;
            navList.appendChild(listItem);
        });

        mobileNav.appendChild(navList);
        document.body.appendChild(mobileNav);
    }

    /**
     * Get appropriate icon for navigation item
     */
    getIconForNavItem(text) {
        const iconMap = {
            'home': '🏠',
            'admin': '👨‍💼',
            'student': '🎓',
            'login': '🔐',
            'schools': '🏫',
            'reports': '📊',
            'students': '👥',
            'settings': '⚙️',
            'logout': '🚪',
            'dashboard': '📋',
            'results': '📝',
            'attendance': '✅',
            'users': '👤',
            'classes': '📚',
            'subjects': '📖'
        };

        const lowerText = text.toLowerCase();
        for (const [key, icon] of Object.entries(iconMap)) {
            if (lowerText.includes(key)) {
                return icon;
            }
        }
        return '📄'; // Default icon
    }

    /**
     * Create navigation overlay
     */
    createNavOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'nav-overlay';
        document.body.appendChild(overlay);
    }

    /**
     * Toggle mobile menu
     */
    toggleMobileMenu() {
        const hamburger = document.querySelector('.hamburger');
        const mobileNav = document.querySelector('.mobile-nav');
        const overlay = document.querySelector('.nav-overlay');

        if (hamburger && mobileNav && overlay) {
            const isActive = hamburger.classList.contains('active');

            if (isActive) {
                this.closeMobileMenu();
            } else {
                this.openMobileMenu();
            }
        }
    }

    /**
     * Open mobile menu
     */
    openMobileMenu() {
        const hamburger = document.querySelector('.hamburger');
        const mobileNav = document.querySelector('.mobile-nav');
        const overlay = document.querySelector('.nav-overlay');

        hamburger?.classList.add('active');
        mobileNav?.classList.add('active');
        overlay?.classList.add('active');

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close mobile menu
     */
    closeMobileMenu() {
        const hamburger = document.querySelector('.hamburger');
        const mobileNav = document.querySelector('.mobile-nav');
        const overlay = document.querySelector('.nav-overlay');

        hamburger?.classList.remove('active');
        mobileNav?.classList.remove('active');
        overlay?.classList.remove('active');

        // Restore body scroll
        document.body.style.overflow = '';
    }

    /**
     * Setup responsive tables
     */
    setupResponsiveTables() {
        // Wrap existing tables in responsive containers
        const tables = document.querySelectorAll('table:not(.responsive-table)');
        tables.forEach(table => {
            // Add responsive class
            table.classList.add('responsive-table');

            // Wrap in responsive container if not already wrapped
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }

            // Add data labels for mobile stacking
            this.addTableDataLabels(table);
        });
    }

    /**
     * Add data labels to table cells for mobile stacking
     */
    addTableDataLabels(table) {
        const headers = table.querySelectorAll('th');
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index].textContent.trim());
                }
            });
        });
    }

    /**
     * Setup touch-friendly enhancements
     */
    setupTouchFriendly() {
        // Ensure minimum touch target size
        const clickableElements = document.querySelectorAll('button, a, input[type="submit"], input[type="button"]');
        clickableElements.forEach(element => {
            const rect = element.getBoundingClientRect();
            if (rect.height < 44) {
                element.style.minHeight = '44px';
                element.style.display = 'flex';
                element.style.alignItems = 'center';
                element.style.justifyContent = 'center';
            }
        });

        // Add touch feedback
        this.setupTouchFeedback();
    }

    /**
     * Setup touch feedback for interactive elements
     */
    setupTouchFeedback() {
        const interactiveElements = document.querySelectorAll('button, a, .card, tr');

        interactiveElements.forEach(element => {
            element.addEventListener('touchstart', () => {
                element.style.transition = 'transform 0.1s ease';
                element.style.transform = 'scale(0.98)';
            });

            element.addEventListener('touchend', () => {
                element.style.transform = 'scale(1)';
            });
        });
    }

    /**
     * Setup responsive forms
     */
    setupResponsiveForms() {
        // Enhance existing forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // Add responsive classes to form elements
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (!input.classList.contains('form-control')) {
                    input.classList.add('form-control');
                }
            });

            // Wrap form groups
            const labels = form.querySelectorAll('label');
            labels.forEach(label => {
                const input = form.querySelector(`#${label.getAttribute('for')}`);
                if (input && !label.closest('.form-group')) {
                    const formGroup = document.createElement('div');
                    formGroup.className = 'form-group';

                    label.parentNode.insertBefore(formGroup, label);
                    formGroup.appendChild(label);
                    if (input.nextSibling === label || input.previousSibling === label) {
                        formGroup.appendChild(input);
                    }
                }
            });
        });
    }

    /**
     * Setup swipe gestures for mobile
     */
    setupSwipeGestures() {
        let startX = 0;
        let startY = 0;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });

        document.addEventListener('touchmove', (e) => {
            if (!startX || !startY) return;

            const diffX = startX - e.touches[0].clientX;
            const diffY = startY - e.touches[0].clientY;

            // Swipe right to open menu (from left edge)
            if (startX < 50 && diffX < -50 && Math.abs(diffY) < 100) {
                this.openMobileMenu();
            }

            // Swipe left to close menu
            if (diffX > 50 && Math.abs(diffY) < 100) {
                const mobileNav = document.querySelector('.mobile-nav');
                if (mobileNav?.classList.contains('active')) {
                    this.closeMobileMenu();
                }
            }
        });

        document.addEventListener('touchend', () => {
            startX = 0;
            startY = 0;
        });
    }

    /**
     * Setup toast notifications
     */
    setupToasts() {
        window.showToast = (message, type = 'success', duration = 3000) => {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;

            document.body.appendChild(toast);

            // Show toast
            setTimeout(() => toast.classList.add('show'), 100);

            // Hide and remove toast
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        };
    }

    /**
     * Enhance existing elements with responsive classes
     */
    enhanceExistingElements() {
        // Add container class to main content areas
        const mainContent = document.querySelector('main') || document.querySelector('.content') || document.querySelector('.container');
        if (mainContent && !mainContent.classList.contains('container')) {
            mainContent.classList.add('container');
        }

        // Enhance buttons
        const buttons = document.querySelectorAll('button, input[type="submit"], input[type="button"]');
        buttons.forEach(button => {
            if (!button.classList.contains('btn')) {
                button.classList.add('btn', 'btn-primary');
            }
        });

        // Enhance cards/panels
        const cards = document.querySelectorAll('.panel, .box, .widget');
        cards.forEach(card => {
            card.classList.add('card');
        });
    }

    /**
     * Handle window resize
     */
    handleResize() {
        // Close mobile menu on desktop
        if (window.innerWidth > 768) {
            this.closeMobileMenu();
        }

        // Refresh table responsiveness
        this.setupResponsiveTables();
    }
}

// Auto-initialize when script loads
const mobileResponsive = new MobileResponsive();

// Handle window resize
window.addEventListener('resize', () => {
    mobileResponsive.handleResize();
});

// Export for manual initialization if needed
window.MobileResponsive = MobileResponsive;
