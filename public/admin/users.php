<?php
declare(strict_types=1);

// Include configuration and bootstrap
require_once __DIR__ . '/../../config/config.php';

// Require admin authentication
require_role('SCHOOL_ADMIN', 'STAFF');

// Page variables
$pageTitle = 'User Management - Uganda Results System';
$pageDescription = 'Manage staff and admin user accounts';
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
            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        User Management
    </h1>
    <p class="page-subtitle">Manage staff accounts, permissions, and administrative users.</p>
</div>

<!-- Action Bar -->
<div class="flex justify-between items-center mb-6">
    <div class="flex gap-3">
        <button class="btn btn-primary" onclick="openAddUserModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            Add New User
        </button>
        <button class="btn btn-outline" onclick="exportUsers()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
            </svg>
            Export Users
        </button>
    </div>

    <div class="flex gap-3 items-center">
        <select class="form-select" style="width: auto;">
            <option>All Roles</option>
            <option>School Admin</option>
            <option>Staff</option>
            <option>Teacher</option>
        </select>
        <input type="search" placeholder="Search users..." class="form-input" style="width: 250px;">
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="card">
        <div class="card-body text-center">
            <div style="width: 48px; height: 48px; background: var(--primary-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: var(--primary-600);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h3 style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: 0.5rem;">23</h3>
            <p style="color: var(--gray-500); margin: 0;">Total Users</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-center">
            <div style="width: 48px; height: 48px; background: var(--primary-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: var(--primary-600);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <h3 style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: 0.5rem;">5</h3>
            <p style="color: var(--gray-500); margin: 0;">Admins</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-center">
            <div style="width: 48px; height: 48px; background: var(--primary-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: var(--primary-600);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2z"/>
                </svg>
            </div>
            <h3 style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: 0.5rem;">18</h3>
            <p style="color: var(--gray-500); margin: 0;">Teaching Staff</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-center">
            <div style="width: 48px; height: 48px; background: var(--primary-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: var(--primary-600);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: 0.5rem;">21</h3>
            <p style="color: var(--gray-500); margin: 0;">Active</p>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Staff & Administrative Users</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                    MN
                                </div>
                                <div>
                                    <div class="font-medium">Mary Nabwire</div>
                                    <div class="text-sm text-gray-500">School Administrator</div>
                                </div>
                            </div>
                        </td>
                        <td>mary.nabwire@school.ug</td>
                        <td><span class="badge badge-primary">School Admin</span></td>
                        <td>Administration</td>
                        <td><span class="badge badge-success">Active</span></td>
                        <td>2 hours ago</td>
                        <td>
                            <div class="flex gap-2">
                                <button class="btn-icon btn-sm" onclick="viewUser('user1')" title="View Details">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                    </svg>
                                </button>
                                <button class="btn-icon btn-sm" onclick="editUser('user1')" title="Edit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                    </svg>
                                </button>
                                <button class="btn-icon btn-sm" onclick="resetPassword('user1')" title="Reset Password">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 17a2 2 0 0 0 2-2 2 2 0 0 0-2-2 2 2 0 0 0-2 2 2 2 0 0 0 2 2m6-9a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V10a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5 5 5 0 0 1 5 5v2h1m-6-5a3 3 0 0 0-3 3v2h6V6a3 3 0 0 0-3-3z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                    JK
                                </div>
                                <div>
                                    <div class="font-medium">John Kiprotich</div>
                                    <div class="text-sm text-gray-500">Mathematics Teacher</div>
                                </div>
                            </div>
                        </td>
                        <td>john.kiprotich@school.ug</td>
                        <td><span class="badge badge-info">Staff</span></td>
                        <td>Mathematics</td>
                        <td><span class="badge badge-success">Active</span></td>
                        <td>1 day ago</td>
                        <td>
                            <div class="flex gap-2">
                                <button class="btn-icon btn-sm" onclick="viewUser('user2')" title="View Details">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                    </svg>
                                </button>
                                <button class="btn-icon btn-sm" onclick="editUser('user2')" title="Edit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                    </svg>
                                </button>
                                <button class="btn-icon btn-sm" onclick="resetPassword('user2')" title="Reset Password">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 17a2 2 0 0 0 2-2 2 2 0 0 0-2-2 2 2 0 0 0-2 2 2 2 0 0 0 2 2m6-9a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V10a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5 5 5 0 0 1 5 5v2h1m-6-5a3 3 0 0 0-3 3v2h6V6a3 3 0 0 0-3-3z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="flex justify-between items-center">
            <p class="text-sm text-gray-500">Showing 1-10 of 23 users</p>
            <div class="flex gap-2">
                <button class="btn btn-outline btn-sm">Previous</button>
                <button class="btn btn-primary btn-sm">1</button>
                <button class="btn btn-outline btn-sm">2</button>
                <button class="btn btn-outline btn-sm">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
// User Management Functions
function openAddUserModal() {
    alert('Add New User modal would open here');
}

function viewUser(userId) {
    alert('View user details for: ' + userId);
}

function editUser(userId) {
    alert('Edit user: ' + userId);
}

function resetPassword(userId) {
    if (confirm('Are you sure you want to reset password for this user?')) {
        alert('Reset password for: ' + userId);
    }
}

function exportUsers() {
    alert('Export users functionality');
}
</script>

<style>
.badge-primary {
    background-color: var(--primary-100);
    color: var(--primary-700);
}
</style>

<?php
// Include footer
include __DIR__ . '/../../views/layouts/footer.php';
?>
