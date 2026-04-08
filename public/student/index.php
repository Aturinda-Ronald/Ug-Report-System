<?php
declare(strict_types=1);

// Include configuration and bootstrap
require_once __DIR__ . '/../../config/config.php';

// Require student authentication
require_role('STUDENT');

// Page variables
$pageTitle = 'Student Portal - Uganda Results System';
$pageDescription = 'Student dashboard for viewing results and reports';
$bodyClass = 'student-page';

// Get current user info
$currentUser = $_SESSION['user_name'] ?? 'Student';
$currentSchool = $_SESSION['school_name'] ?? 'School';
$studentId = $_SESSION['student_id'] ?? null;
$userRole = get_user_role();

// Include header
include __DIR__ . '/../../views/layouts/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
        </svg>
        Student Portal
    </h1>
    <p class="page-subtitle">Access your academic records and results from <?php echo htmlspecialchars($currentSchool); ?>.</p>
</div>

<!-- User Profile Card -->
<div class="user-profile-card">
    <div class="profile-card-content">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($currentUser, 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h3><?php echo htmlspecialchars($currentUser); ?></h3>
            <div class="profile-details">
                <span><strong>Student ID:</strong> <?php echo $studentId ?? 'N/A'; ?></span>
                <span><strong>School:</strong> <?php echo htmlspecialchars($currentSchool); ?></span>
                <span><strong>Academic Year:</strong> <?php echo date('Y'); ?></span>
            </div>
        </div>
    </div>
</div>

    <!-- Main Content -->
    <div class="container py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Student Portal</h1>
            <p class="text-secondary">View your academic results and download report cards</p>
        </div>

        <!-- Quick Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-primary-600 mb-2">--</div>
                    <div class="text-sm text-secondary">Current Term</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-success-600 mb-2">--</div>
                    <div class="text-sm text-secondary">Subjects</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-warning-600 mb-2">--</div>
                    <div class="text-sm text-secondary">Class Position</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-info-600 mb-2">--</div>
                    <div class="text-sm text-secondary">Reports Available</div>
                </div>
            </div>
        </div>

        <!-- Main Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Academic Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">Academic Information</h3>
                </div>
                <div class="card-body">
                    <div class="grid gap-3">
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                            </svg>
                            My Subjects
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                            </svg>
                            Current Results
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2.5 2.25l1.41-1.41L15 12.42V7H9v5.42l1.91 1.91 1.41-1.41L11 11.59V8h2v3.59l1.32 1.32z"/>
                            </svg>
                            Performance Analytics
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2-7H5c-1.1 0-2 .9-2 2v3h2V6h14v2h2V6c0-1.1-.9-2-2-2z"/>
                            </svg>
                            Attendance Record
                        </a>
                    </div>
                </div>
            </div>

            <!-- Reports & Documents -->
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">Reports & Documents</h3>
                </div>
                <div class="card-body">
                    <div class="grid gap-3">
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                            </svg>
                            My Report Cards
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Academic Transcripts
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                            </svg>
                            Print Reports
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 2 2h10v-2H6V4h7v5h5v1.67c.88.09 1.66.45 2.29 1.03.4-.6.71-1.29.71-2.03V8l-6-6H6z"/>
                            </svg>
                            Download Documents
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="mt-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">Personal Information</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium mb-3">Student Details</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-secondary">Index Number:</span>
                                    <span class="font-medium">--</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-secondary">Class & Stream:</span>
                                    <span class="font-medium">--</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-secondary">Academic Level:</span>
                                    <span class="font-medium">--</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-secondary">Admission Year:</span>
                                    <span class="font-medium">--</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium mb-3">Quick Actions</h4>
                            <div class="grid gap-2">
                                <a href="#" class="btn btn-sm btn-outline">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                    Update Profile
                                </a>
                                <a href="#" class="btn btn-sm btn-outline">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                    </svg>
                                    Change Password
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Development Notice -->
        <div class="mt-8">
            <div class="alert alert-info">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
                <div>
                    <strong>Development Phase:</strong> This student portal is currently under development.
                    Features like viewing results, downloading reports, and profile management will be available soon.
                    <div class="mt-2">
                        <strong>Current Status:</strong> Authentication system is complete and working.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$pageJs = "
console.log('Student portal loaded');
";

// Include footer
include __DIR__ . '/../../views/layouts/footer.php';
?>
