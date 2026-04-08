<?php
declare(strict_types=1);

// Include configuration and bootstrap
require_once __DIR__ . '/../../config/config.php';

// Require admin authentication
require_role('SCHOOL_ADMIN', 'STAFF');

// Page variables
$pageTitle = 'Report Generation - Uganda Results System';
$pageDescription = 'Generate and export student reports';
$bodyClass = 'admin-page';

// Get current user and school info
$currentUser = $_SESSION['user_name'] ?? 'Administrator';
$currentSchool = $_SESSION['school_name'] ?? 'School';
$userRole = get_user_role();

// Include header
include __DIR__ . '/../../views/layouts/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
        </svg>
        Report Generation
    </h1>
    <p class="page-subtitle">Generate and export professional student reports and report cards.</p>
</div>

<div class="card">
    <div class="card-body text-center" style="padding: 3rem;">
        <div style="width: 64px; height: 64px; background: var(--gray-100); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--gray-400);">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
            </svg>
        </div>
        <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; color: var(--gray-900);">Report Cards & Documents</h3>
        <p style="color: var(--gray-500); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
            This page will contain tools to generate professional report cards, transcripts, class lists, performance analytics, and other essential academic documents with school branding.
        </p>
        <div class="flex gap-3 justify-center">
            <button class="btn btn-primary">Generate Report Cards</button>
            <button class="btn btn-outline">Class Reports</button>
            <a href="<?php echo base_url('admin/'); ?>" class="btn btn-outline">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../../views/layouts/footer.php';
?>
