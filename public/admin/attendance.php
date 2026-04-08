<?php
declare(strict_types=1);

// Include configuration and bootstrap
require_once __DIR__ . '/../../config/config.php';

// Require admin authentication
require_role('SCHOOL_ADMIN', 'STAFF');

// Page variables
$pageTitle = 'Attendance Management - Uganda Results System';
$pageDescription = 'Track and manage student attendance';
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
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Attendance Management
    </h1>
    <p class="page-subtitle">Track daily attendance and generate attendance reports.</p>
</div>

<div class="card">
    <div class="card-body text-center" style="padding: 3rem;">
        <div style="width: 64px; height: 64px; background: var(--gray-100); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--gray-400);">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; color: var(--gray-900);">Student Attendance Tracking</h3>
        <p style="color: var(--gray-500); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
            This page will contain tools to mark daily attendance, track absences, generate attendance reports, and monitor student participation patterns.
        </p>
        <div class="flex gap-3 justify-center">
            <button class="btn btn-primary">Mark Attendance</button>
            <button class="btn btn-outline">Attendance Reports</button>
            <a href="<?php echo base_url('admin/'); ?>" class="btn btn-outline">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../../views/layouts/footer.php';
?>
