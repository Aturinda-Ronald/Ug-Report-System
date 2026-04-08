<?php
// Page variables
$pageTitle = '403 - Access Denied - Uganda Results System';
$pageDescription = 'You do not have permission to access this resource';
$bodyClass = 'error-page';

// Include header
include __DIR__ . '/../../views/layouts/header.php';
?>

<div class="min-h-screen bg-secondary flex items-center justify-center py-12">
    <div class="container text-center">
        <div class="max-w-lg mx-auto">
            <!-- Error Icon -->
            <div class="mb-8">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="currentColor" class="mx-auto text-error-600">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11V12z"/>
                </svg>
            </div>

            <!-- Error Message -->
            <h1 class="text-6xl font-bold text-error-600 mb-4">403</h1>
            <h2 class="text-2xl font-semibold mb-4">Access Denied</h2>
            <p class="text-lg text-secondary mb-8">
                You don't have permission to access this resource. Please contact your administrator if you believe this is an error.
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo base_url(); ?>" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>
                    Go Home
                </a>
                <a href="<?php echo base_url('auth/logout.php'); ?>" class="btn btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                    Logout
                </a>
            </div>

            <!-- Contact Information -->
            <div class="mt-12 pt-8 border-t border-border-color">
                <h3 class="font-semibold mb-4">Need Help?</h3>
                <p class="text-sm text-secondary">
                    If you believe you should have access to this page, please contact your school administrator
                    or system support at <a href="mailto:support@uganda-results.com" class="text-primary-600">support@uganda-results.com</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../../views/layouts/footer.php';
?>
