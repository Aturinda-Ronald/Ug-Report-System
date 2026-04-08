<?php
declare(strict_types=1);

// Include configuration and bootstrap
require_once __DIR__ . '/../../config/config.php';

// Require super admin authentication
require_role('SUPER_ADMIN');

// Page variables
$pageTitle = 'Super Admin Dashboard - Uganda Results System';
$pageDescription = 'Platform administration dashboard';
$bodyClass = 'super-admin-page';

// Get current user info
$currentUser = $_SESSION['user_name'] ?? 'Super Administrator';
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
        Super Admin Dashboard
    </h1>
    <p class="page-subtitle">Platform administration and system-wide management.</p>
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
                <span><strong>Role:</strong> Super Administrator</span>
                <span><strong>Access Level:</strong> System Wide</span>
                <span><strong>Last Login:</strong> <?php echo date('M d, Y \a\t g:i A'); ?></span>
            </div>
        </div>
    </div>
</div>

    <!-- Main Content -->
    <div class="container py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Super Admin Dashboard</h1>
            <p class="text-secondary">Manage the entire Uganda Results System platform</p>
        </div>

        <!-- Platform Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-primary-600 mb-2">2</div>
                    <div class="text-sm text-secondary">Total Schools</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-success-600 mb-2">16</div>
                    <div class="text-sm text-secondary">Total Students</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-warning-600 mb-2">5</div>
                    <div class="text-sm text-secondary">System Users</div>
                </div>
            </div>
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-3xl font-bold text-info-600 mb-2">--</div>
                    <div class="text-sm text-secondary">Active Sessions</div>
                </div>
            </div>
        </div>

        <!-- Management Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- School Management -->
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">School Management</h3>
                </div>
                <div class="card-body">
                    <div class="grid gap-3">
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                            </svg>
                            View All Schools
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                            </svg>
                            Create New School
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            School Performance
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Impersonate Admin
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Management -->
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">System Management</h3>
                </div>
                <div class="card-body">
                    <div class="grid gap-3">
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
                            </svg>
                            System Settings
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Audit Logs
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.997 2.997 0 0 0 17.11 7H16.5l-.09-.4c-.32-1.49-1.65-2.6-3.24-2.6s-2.92 1.11-3.24 2.6L9.84 7H9.1c-1.35 0-2.53.88-2.92 2.16L3.5 16H6v6h4v-6h4v6h6z"/>
                            </svg>
                            User Management
                        </a>
                        <a href="#" class="btn btn-outline text-left justify-start">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2.5 2.25l1.41-1.41L15 12.42V7H9v5.42l1.91 1.91 1.41-1.41L11 11.59V8h2v3.59l1.32 1.32z"/>
                            </svg>
                            Platform Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Schools -->
        <div class="mt-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold">Recent Schools</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>School Name</th>
                                    <th>Location</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Last Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="font-medium">Makerere College School</div>
                                        <div class="text-sm text-secondary">EMIS: UG001</div>
                                    </td>
                                    <td>Kampala</td>
                                    <td>16</td>
                                    <td>
                                        <span class="inline-flex px-2 py-1 text-xs rounded-full bg-success-100 text-success-800">
                                            Active
                                        </span>
                                    </td>
                                    <td>Today</td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a href="#" class="btn btn-sm btn-outline">View</a>
                                            <a href="#" class="btn btn-sm btn-primary">Manage</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="font-medium">St. Mary's Secondary School Kisubi</div>
                                        <div class="text-sm text-secondary">EMIS: UG002</div>
                                    </td>
                                    <td>Wakiso</td>
                                    <td>0</td>
                                    <td>
                                        <span class="inline-flex px-2 py-1 text-xs rounded-full bg-warning-100 text-warning-800">
                                            Setup Pending
                                        </span>
                                    </td>
                                    <td>2 days ago</td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a href="#" class="btn btn-sm btn-outline">View</a>
                                            <a href="#" class="btn btn-sm btn-primary">Setup</a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                    <strong>Development Phase:</strong> This super admin dashboard is currently under development.
                    Advanced features like school management, audit logs, and platform analytics will be available soon.
                    <div class="mt-2">
                        <strong>Current Status:</strong> Multi-tenant foundation and authentication are complete.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$pageJs = "
console.log('Super admin dashboard loaded');
";

// Include footer
include __DIR__ . '/../../views/layouts/footer.php';
?>
