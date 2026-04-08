<?php
// Page variables
$pageTitle = 'Admin & Staff Login - Uganda Results System';
$pageDescription = 'Login to Uganda Results System as administrator or staff member';
$bodyClass = 'auth-page';
$robots = 'noindex, nofollow';

// Include header
include __DIR__ . '/../layouts/header.php';

// Display flash messages
$messages = [
    'success' => $_SESSION['success_message'] ?? null,
    'error' => $_SESSION['error_message'] ?? null,
    'info' => $_SESSION['info_message'] ?? null
];

// Clear messages after displaying
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['info_message']);
?>

<div class="auth-container">
    <div class="auth-header">
        <div class="auth-logo">
            <svg width="64" height="64" viewBox="0 0 64 64" class="logo">
                <circle cx="32" cy="32" r="30" fill="var(--primary-600)"/>
                <rect x="16" y="18" width="20" height="24" rx="2" fill="white"/>
                <rect x="18" y="20" width="16" height="20" fill="#f3f4f6"/>
                <line x1="20" y1="24" x2="32" y2="24" stroke="#6b7280" stroke-width="1"/>
                <line x1="20" y1="27" x2="32" y2="27" stroke="#6b7280" stroke-width="1"/>
                <line x1="20" y1="30" x2="30" y2="30" stroke="#6b7280" stroke-width="1"/>
                <text x="24" y="36" text-anchor="middle" fill="#059669" font-family="Arial" font-size="6" font-weight="bold">A</text>
            </svg>
            <h1><?php echo APP_NAME; ?></h1>
        </div>
    </div>

    <div class="auth-content">
        <div class="auth-form-header">
            <h2>Admin & Staff Login</h2>
            <p class="text-secondary">Sign in to your account to continue</p>
        </div>

        <?php if ($messages['success']): ?>
            <div class="alert alert-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                <?php echo htmlspecialchars($messages['success']); ?>
            </div>
        <?php endif; ?>

        <?php if ($messages['error']): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
                <?php echo htmlspecialchars($messages['error']); ?>
            </div>
        <?php endif; ?>

        <?php if ($messages['info']): ?>
            <div class="alert alert-info">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
                <?php echo htmlspecialchars($messages['info']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['login'])): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
                <?php echo htmlspecialchars($errors['login']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo base_url('auth/login-admin.php'); ?>" class="auth-form admin-login-form" data-validate novalidate>
            <input type="hidden" name="_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="relative">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input <?php echo !empty($errors['email']) ? 'error' : ''; ?>"
                        value="<?php echo htmlspecialchars($old_input['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
                        placeholder="Enter your email address"
                    >
                    <div class="absolute inset-y-0 left-3 flex items-center">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="text-secondary">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                </div>
                <?php if (!empty($errors['email'])): ?>
                    <span class="form-error"><?php echo htmlspecialchars($errors['email']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="relative">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input <?php echo !empty($errors['password']) ? 'error' : ''; ?>"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        minlength="6"
                    >
                    <div class="absolute inset-y-0 left-3 flex items-center">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="text-secondary">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                    </div>
                    <button type="button" class="absolute inset-y-0 right-3 flex items-center password-toggle" title="Show password">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="text-secondary">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <span class="form-error"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember" class="form-label mb-0">Remember me</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Sign In
                </button>
            </div>

            <div class="form-group text-center">
                <a href="<?php echo base_url('auth/forgot.php'); ?>" class="text-sm text-primary-600 hover:text-primary-700">
                    Forgot your password?
                </a>
            </div>
        </form>

        <div class="auth-form-footer">
            <div class="text-center">
                <p class="text-sm text-secondary">Are you a student?</p>
                <a href="<?php echo base_url('auth/login-student.php'); ?>" class="btn btn-outline btn-sm mt-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    Student Login
                </a>
            </div>
        </div>
    </div>

    <div class="auth-footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        <div class="auth-links">
            <a href="<?php echo base_url(); ?>">Home</a>
            <a href="<?php echo base_url('auth/login-admin.php'); ?>">Admin Login</a>
            <a href="<?php echo base_url('auth/login-student.php'); ?>">Student Login</a>
        </div>
    </div>
</div>

<style>
.auth-form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-form-header h2 {
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-size: 1.5rem;
    font-weight: 600;
}

.auth-form-footer {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.relative {
    position: relative;
}

.absolute {
    position: absolute;
}

.inset-y-0 {
    top: 0;
    bottom: 0;
}

.left-3 {
    left: 0.75rem;
}

.right-3 {
    right: 0.75rem;
}

.password-toggle {
    background: none;
    border: none;
    cursor: pointer;
    color: inherit;
}

.form-input {
    padding-left: 2.5rem;
    padding-right: 2.5rem;
}

.checkbox-wrapper label {
    cursor: pointer;
    user-select: none;
}
</style>

<?php
// Page-specific JavaScript
$pageJs = "
// Password toggle functionality
document.querySelectorAll('.password-toggle').forEach(toggle => {
    toggle.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);

        // Update icon
        const icon = this.querySelector('svg path');
        if (type === 'text') {
            icon.setAttribute('d', 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L15 15');
        } else {
            icon.setAttribute('d', 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z');
        }
    });
});

// Remember email functionality
const rememberCheckbox = document.querySelector('input[name=\"remember\"]');
const emailInput = document.querySelector('input[name=\"email\"]');

if (rememberCheckbox && emailInput) {
    // Load remembered email
    const rememberedEmail = localStorage.getItem('remembered_email');
    if (rememberedEmail) {
        emailInput.value = rememberedEmail;
        rememberCheckbox.checked = true;
    }

    // Save email on form submit
    document.querySelector('.auth-form').addEventListener('submit', function() {
        if (rememberCheckbox.checked && emailInput.value) {
            localStorage.setItem('remembered_email', emailInput.value);
        } else {
            localStorage.removeItem('remembered_email');
        }
    });
}
";

// Additional JS files
$additionalJs = ['js/auth.js'];

// Include footer
include __DIR__ . '/../layouts/footer.php';
?>
