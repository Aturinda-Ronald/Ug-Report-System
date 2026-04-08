<?php
// Page variables
$pageTitle = '404 - Page Not Found - Uganda Results System';
$pageDescription = 'The page you are looking for could not be found';
$bodyClass = 'error-page';

// Include header
include __DIR__ . '/../../views/layouts/header.php';
?>

<div class="min-h-screen bg-secondary flex items-center justify-center py-12">
    <div class="container text-center">
        <div class="max-w-lg mx-auto">
            <!-- Error Icon -->
            <div class="mb-8">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="currentColor" class="mx-auto text-primary-600">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
            </div>

            <!-- Error Message -->
            <h1 class="text-6xl font-bold text-primary-600 mb-4">404</h1>
            <h2 class="text-2xl font-semibold mb-4">Page Not Found</h2>
            <p class="text-lg text-secondary mb-8">
                Sorry, the page you are looking for doesn't exist or has been moved.
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo base_url(); ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>
                    Go Home
                </a>
                <button onclick="history.back()" class="btn btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                    </svg>
                    Go Back
                </button>
            </div>

            <!-- Helpful Links -->
            <div class="mt-12 pt-8 border-t border-border-color">
                <h3 class="font-semibold mb-4">You might be looking for:</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <a href="<?php echo base_url('auth/login-admin.php'); ?>" class="text-primary-600 hover:text-primary-700">
                        Admin Login
                    </a>
                    <a href="<?php echo base_url('auth/login-student.php'); ?>" class="text-primary-600 hover:text-primary-700">
                        Student Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../../views/layouts/footer.php';
?>
