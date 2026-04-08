/**
 * Uganda High School Results & Report Card System
 * Main JavaScript File - Modern & Clean
 */

// Application object
window.UgandaResults = {
    // Configuration
    config: {
        baseUrl: window.location.origin,
        theme: localStorage.getItem('theme') || 'light',
        csrfToken: null
    },

    // Initialize application
    init: function() {
        this.setupTheme();
        this.setupMobileMenu();
        this.setupForms();
        this.setupTables();
        this.setupLoading();
        this.setupCsrfToken();
        this.setupNotifications();
        // Ensure any loader is hidden on first paint and when navigating back/forward (bfcache)
        var _hide = this.hideLoading.bind(this);
        window.addEventListener('pageshow', function(e){ _hide(); }, { passive: true });
        window.addEventListener('load', _hide, { once: true });
        document.addEventListener('visibilitychange', function(){ if (document.visibilityState === 'visible') _hide(); }, { passive: true });

        console.log('Uganda Results System initialized');
    },

    // Theme management
    setupTheme: function() {
        const html = document.documentElement;
        html.setAttribute('data-theme', this.config.theme);

        // Theme toggle button
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const newTheme = this.config.theme === 'light' ? 'dark' : 'light';
                this.setTheme(newTheme);
            });
        }
    },

    setTheme: function(theme) {
        this.config.theme = theme;
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-theme', theme);

        // Update theme toggle icon
        this.updateThemeIcon();
    },

    updateThemeIcon: function() {
        // Icons are handled via CSS based on data-theme attribute
    },

    // Mobile menu
    setupMobileMenu: function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                mainContent?.classList.toggle('expanded');
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                    mainContent?.classList.remove('expanded');
                }
            });
        }
    },

    // Form enhancements
    setupForms: function() {
        // Auto-submit forms with data-auto-submit
        document.querySelectorAll('form[data-auto-submit]').forEach(form => {
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    form.submit();
                });
            });
        });

        // Confirm forms
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', (e) => {
                const message = form.dataset.confirm;
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });

        // File input preview
        document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
            input.addEventListener('change', (e) => {
                this.previewFile(e.target);
            });
        });

        // Form validation
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });

        // Real-time validation
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });

            field.addEventListener('input', () => {
                this.clearFieldError(field);
            });
        });
    },

    // File preview
    previewFile: function(input) {
        const file = input.files[0];
        const previewId = input.dataset.preview;
        const preview = document.getElementById(previewId);

        if (file && preview) {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (file.type.startsWith('image/')) {
                    preview.innerHTML = `
                        <div class="preview-image">
                            <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                    `;
                } else {
                    preview.innerHTML = `
                        <div class="preview-file">
                            <div class="file-icon">📄</div>
                            <span>${file.name}</span>
                        </div>
                    `;
                }
            };
            reader.readAsDataURL(file);
        }
    },

    // Form validation
    validateForm: function(form) {
        let valid = true;
        const fields = form.querySelectorAll('input, select, textarea');

        fields.forEach(field => {
            if (!this.validateField(field)) {
                valid = false;
            }
        });

        // Password confirmation
        const password = form.querySelector('input[name="password"]');
        const passwordConfirm = form.querySelector('input[name="password_confirm"]');

        if (password && passwordConfirm && password.value !== passwordConfirm.value) {
            valid = false;
            this.showFieldError(passwordConfirm, 'Passwords do not match');
        }

        return valid;
    },

    validateField: function(field) {
        const value = field.value.trim();
        const rules = this.getValidationRules(field);

        // Clear previous errors
        this.clearFieldError(field);

        // Required validation
        if (rules.required && !value) {
            this.showFieldError(field, 'This field is required');
            return false;
        }

        // Skip other validations if field is empty and not required
        if (!value && !rules.required) {
            return true;
        }

        // Email validation
        if (rules.email && !this.isValidEmail(value)) {
            this.showFieldError(field, 'Please enter a valid email address');
            return false;
        }

        // Minimum length validation
        if (rules.minLength && value.length < rules.minLength) {
            this.showFieldError(field, `Minimum ${rules.minLength} characters required`);
            return false;
        }

        // Maximum length validation
        if (rules.maxLength && value.length > rules.maxLength) {
            this.showFieldError(field, `Maximum ${rules.maxLength} characters allowed`);
            return false;
        }

        // Pattern validation
        if (rules.pattern && !rules.pattern.test(value)) {
            this.showFieldError(field, rules.patternMessage || 'Invalid format');
            return false;
        }

        return true;
    },

    getValidationRules: function(field) {
        const rules = {
            required: field.hasAttribute('required'),
            email: field.type === 'email',
            minLength: field.getAttribute('minlength') ? parseInt(field.getAttribute('minlength')) : null,
            maxLength: field.getAttribute('maxlength') ? parseInt(field.getAttribute('maxlength')) : null
        };

        // allow any non-empty text (or remove the whole block)
        if (field.name === 'index_no') {
            // do not set rules.pattern at all
            // (optional) only require something is provided:
            // rules.pattern = /^.+$/;
            // rules.patternMessage = 'Please enter an index number';
        }

        if (field.type === 'tel') {
            rules.pattern = /^[\+]?[0-9\-\s\(\)]+$/;
            rules.patternMessage = 'Please enter a valid phone number';
        }

        return rules;
    },

    showFieldError: function(field, message) {
        field.classList.add('error');

        // Remove existing error
        const existingError = field.parentNode.querySelector('.form-error');
        if (existingError) {
            existingError.remove();
        }

        // Add new error
        const errorElement = document.createElement('span');
        errorElement.className = 'form-error';
        errorElement.textContent = message;
        field.parentNode.appendChild(errorElement);
    },

    clearFieldError: function(field) {
        field.classList.remove('error');
        const errorElement = field.parentNode.querySelector('.form-error');
        if (errorElement) {
            errorElement.remove();
        }
    },

    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Table enhancements
    setupTables: function() {
        // Sortable tables
        document.querySelectorAll('table[data-sortable] th[data-sort]').forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });

        // Searchable tables
        document.querySelectorAll('input[data-table-search]').forEach(searchInput => {
            const tableId = searchInput.dataset.tableSearch;
            const table = document.getElementById(tableId);

            if (table) {
                searchInput.addEventListener('input', this.debounce(() => {
                    this.searchTable(table, searchInput.value);
                }, 300));
            }
        });

        // Row selection
        document.querySelectorAll('table[data-selectable]').forEach(table => {
            this.setupTableSelection(table);
        });
    },

    sortTable: function(header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const column = Array.from(header.parentNode.children).indexOf(header);
        const currentOrder = header.dataset.order || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

        // Clear other headers
        table.querySelectorAll('th[data-sort]').forEach(th => {
            th.removeAttribute('data-order');
            th.classList.remove('sort-asc', 'sort-desc');
        });

        // Set current header
        header.dataset.order = newOrder;
        header.classList.add(`sort-${newOrder}`);

        // Sort rows
        rows.sort((a, b) => {
            const aVal = a.cells[column]?.textContent.trim() || '';
            const bVal = b.cells[column]?.textContent.trim() || '';

            // Try to parse as numbers
            const aNum = parseFloat(aVal);
            const bNum = parseFloat(bVal);

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return newOrder === 'asc' ? aNum - bNum : bNum - aNum;
            }

            // String comparison
            return newOrder === 'asc'
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal);
        });

        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    },

    searchTable: function(table, query) {
        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        const searchQuery = query.toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(searchQuery);
            row.style.display = matches ? '' : 'none';
        });

        // Update result count if exists
        const resultCount = document.querySelector('[data-search-results]');
        if (resultCount) {
            const visibleRows = tbody.querySelectorAll('tr:not([style*="display: none"])').length;
            resultCount.textContent = `${visibleRows} results`;
        }
    },

    setupTableSelection: function(table) {
        const selectAll = table.querySelector('input[data-select-all]');
        const checkboxes = table.querySelectorAll('input[data-select-row]');

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                this.updateSelectionCount(table);
            });
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                if (selectAll) {
                    selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
                }
                this.updateSelectionCount(table);
            });
        });
    },

    updateSelectionCount: function(table) {
        const checkboxes = table.querySelectorAll('input[data-select-row]:checked');
        const countElement = document.querySelector('[data-selection-count]');

        if (countElement) {
            countElement.textContent = checkboxes.length;
        }

        // Show/hide bulk actions
        const bulkActions = document.querySelector('[data-bulk-actions]');
        if (bulkActions) {
            bulkActions.style.display = checkboxes.length > 0 ? 'block' : 'none';
        }
    },

    // Loading states
    setupLoading: function() {
        // Show loading on form submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                this.showLoading();
            });
        });

        // Show loading on navigation (same-origin full navigations only)
        document.querySelectorAll('a[href]:not([target="_blank"])').forEach(link => {
            link.addEventListener('click', (ev) => {
                try {
                    const href = link.getAttribute('href') || '';
                    if (href.startsWith('#') || href.startsWith('javascript:') || link.hasAttribute('download')) return;

                    const url = new URL(href, window.location.href);
                    const sameOrigin = url.origin === window.location.origin;
                    const isAnchorOnly = (url.hash && url.pathname === location.pathname && url.search === location.search);
                    if (!sameOrigin || isAnchorOnly) return;

                    // Defer so navigation isn’t blocked
                    setTimeout(() => this.showLoading(), 0);
                } catch(e) {
                    // safest: do nothing
                }
            }, true);
        });
    },

    showLoading: function() {
        const loading = document.getElementById('loading');
        if (loading) {
            loading.style.display = 'flex'; document.body.classList.add('is-loading');
        }
    },

    hideLoading: function() {
        const loading = document.getElementById('loading');
        if (loading) {
            loading.style.display = 'none'; document.body.classList.remove('is-loading');
        }
    },

    // CSRF token management
    setupCsrfToken: function() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token) {
            this.config.csrfToken = token;
        }
    },

    // Notification system
    setupNotifications: function() {
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }, 5000);
        });
    },

    // Show notification
    notify: function(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="notification-icon">
                    ${this.getNotificationIcon(type)}
                </div>
                <div class="flex-1">
                    <span>${message}</span>
                </div>
                <button type="button" class="notification-close ml-4" onclick="this.parentElement.parentElement.remove()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
        `;

        // Style the notification
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            maxWidth: '400px',
            zIndex: '9999',
            animation: 'slideInRight 0.3s ease'
        });

        document.body.appendChild(notification);

        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, duration);
        }
    },

    getNotificationIcon: function(type) {
        const icons = {
            success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
            warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'
        };
        return icons[type] || icons.info;
    },

    // AJAX helpers
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (this.config.csrfToken) {
            defaults.headers['X-CSRF-TOKEN'] = this.config.csrfToken;
        }

        const config = Object.assign(defaults, options);

        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                }
                return response.text();
            });
    },

    // Utility functions
    formatNumber: function(number, decimals = 2) {
        return parseFloat(number).toFixed(decimals);
    },

    formatCurrency: function(amount, currency = 'UGX') {
        return new Intl.NumberFormat('en-UG', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    formatDate: function(date, format = 'short') {
        const options = {
            short: { year: 'numeric', month: 'short', day: 'numeric' },
            long: { year: 'numeric', month: 'long', day: 'numeric' },
            full: { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
        };

        return new Date(date).toLocaleDateString('en-UG', options[format] || options.short);
    },

    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.UgandaResults.init();
});

// Add slide animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }

    .notification-close {
        background: none;
        border: none;
        cursor: pointer;
        color: inherit;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .notification-close:hover {
        opacity: 1;
    }
`;
document.head.appendChild(style);

// Expose globally for easier access
window.notify = window.UgandaResults.notify.bind(window.UgandaResults);
window.ajax = window.UgandaResults.ajax.bind(window.UgandaResults);
