<?php
/**
 * Example: Converting an Existing Page to Mobile Responsive
 * This shows how to convert your existing admin/student pages to use the new responsive features
 */

// Include configuration
require_once __DIR__ . '/config/config.php';

// Page configuration for responsive header
$pageTitle = 'Student Results - Uganda Results System';
$pageHeader = [
    'title' => '📝 Student Results',
    'description' => 'View and manage student academic performance'
];

$breadcrumbs = [
    ['text' => 'Dashboard', 'href' => base_url('admin/')],
    ['text' => 'Students', 'href' => base_url('admin/students.php')],
    ['text' => 'Results', 'href' => '#']
];

$bodyClass = 'results-page';

// Simulate session data for demo
$_SESSION['user_id'] = 'demo';
$_SESSION['role'] = 'SCHOOL_ADMIN';
$_SESSION['first_name'] = 'Demo Teacher';
$_SESSION['school_name'] = 'Uganda Demo High School';

// Include responsive header (replaces your old header)
include __DIR__ . '/views/layouts/responsive-header.php';
?>

<!--
BEFORE: Your old page structure might look like this:
<html>
<head><title>Results</title></head>
<body>
    <header>
        <h1>Admin Panel</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="students.php">Students</a>
        </nav>
    </header>
-->

<!--
AFTER: Use responsive components and classes
All the header, navigation, and mobile features are now handled by the responsive header!
-->

<!-- Quick Actions Card -->
<div class="card mb-3">
    <div class="card-header">
        <h3>📊 Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-6 col-4">
                <button class="btn btn-primary btn-block" onclick="showToast('Add new result feature!', 'info')">
                    ➕ Add New Result
                </button>
            </div>
            <div class="col-6 col-4">
                <button class="btn btn-success btn-block" onclick="showToast('Export feature!', 'success')">
                    📤 Export Results
                </button>
            </div>
            <div class="col-12 col-4">
                <button class="btn btn-warning btn-block" onclick="showToast('Import feature!', 'warning')">
                    📥 Import Results
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card mb-3">
    <div class="card-header">
        <h3>🔍 Search & Filter</h3>
    </div>
    <div class="card-body">
        <form class="search-form" onsubmit="return searchResults(event)">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="search-student">Student Name</label>
                        <input type="text" id="search-student" class="form-control"
                               placeholder="Enter student name...">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="filter-class">Class</label>
                        <select id="filter-class" class="form-control">
                            <option value="">All Classes</option>
                            <option value="s1">Senior 1</option>
                            <option value="s2">Senior 2</option>
                            <option value="s3">Senior 3</option>
                            <option value="s4">Senior 4</option>
                            <option value="s5">Senior 5</option>
                            <option value="s6">Senior 6</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="filter-term">Term</label>
                        <select id="filter-term" class="form-control">
                            <option value="">All Terms</option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="filter-year">Academic Year</label>
                        <select id="filter-year" class="form-control">
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                            <option value="2022">2022</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="demo-actions">
                <button type="submit" class="btn btn-primary">
                    🔍 Search Results
                </button>
                <button type="reset" class="btn btn-secondary">
                    🔄 Clear Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card mb-3">
    <div class="card-header">
        <h3>📋 Student Results</h3>
        <small class="text-muted">Showing results for Term 1, 2024</small>
    </div>
    <div class="card-body">
        <!-- Responsive table wrapper -->
        <div class="table-responsive">
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th class="hide-mobile">Reg No.</th>
                        <th>Mathematics</th>
                        <th>English</th>
                        <th class="hide-mobile">Science</th>
                        <th class="hide-mobile">History</th>
                        <th>Average</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-label="Student Name">
                            <strong>Nakato Sarah</strong>
                        </td>
                        <td data-label="Class">Senior 4A</td>
                        <td data-label="Reg No." class="hide-mobile">2024/001</td>
                        <td data-label="Mathematics"><span class="grade-badge grade-good">85</span></td>
                        <td data-label="English"><span class="grade-badge grade-good">78</span></td>
                        <td data-label="Science" class="hide-mobile"><span class="grade-badge grade-excellent">92</span></td>
                        <td data-label="History" class="hide-mobile"><span class="grade-badge grade-good">80</span></td>
                        <td data-label="Average"><strong class="grade-excellent">84%</strong></td>
                        <td data-label="Grade"><span class="grade-badge grade-excellent">A</span></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="viewStudent(1)">
                                    👁️ View
                                </button>
                                <button class="btn btn-sm btn-success d-none-mobile" onclick="editStudent(1)">
                                    ✏️ Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td data-label="Student Name">
                            <strong>Musoke David</strong>
                        </td>
                        <td data-label="Class">Senior 3B</td>
                        <td data-label="Reg No." class="hide-mobile">2024/002</td>
                        <td data-label="Mathematics"><span class="grade-badge grade-average">65</span></td>
                        <td data-label="English"><span class="grade-badge grade-good">72</span></td>
                        <td data-label="Science" class="hide-mobile"><span class="grade-badge grade-good">78</span></td>
                        <td data-label="History" class="hide-mobile"><span class="grade-badge grade-average">68</span></td>
                        <td data-label="Average"><strong class="grade-good">71%</strong></td>
                        <td data-label="Grade"><span class="grade-badge grade-good">B</span></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="viewStudent(2)">
                                    👁️ View
                                </button>
                                <button class="btn btn-sm btn-success d-none-mobile" onclick="editStudent(2)">
                                    ✏️ Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td data-label="Student Name">
                            <strong>Aisha Namukasa</strong>
                        </td>
                        <td data-label="Class">Senior 4A</td>
                        <td data-label="Reg No." class="hide-mobile">2024/003</td>
                        <td data-label="Mathematics"><span class="grade-badge grade-excellent">91</span></td>
                        <td data-label="English"><span class="grade-badge grade-excellent">87</span></td>
                        <td data-label="Science" class="hide-mobile"><span class="grade-badge grade-excellent">95</span></td>
                        <td data-label="History" class="hide-mobile"><span class="grade-badge grade-excellent">89</span></td>
                        <td data-label="Average"><strong class="grade-excellent">91%</strong></td>
                        <td data-label="Grade"><span class="grade-badge grade-excellent">A+</span></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="viewStudent(3)">
                                    👁️ View
                                </button>
                                <button class="btn btn-sm btn-success d-none-mobile" onclick="editStudent(3)">
                                    ✏️ Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td data-label="Student Name">
                            <strong>Ochieng Peter</strong>
                        </td>
                        <td data-label="Class">Senior 2C</td>
                        <td data-label="Reg No." class="hide-mobile">2024/004</td>
                        <td data-label="Mathematics"><span class="grade-badge grade-poor">45</span></td>
                        <td data-label="English"><span class="grade-badge grade-average">58</span></td>
                        <td data-label="Science" class="hide-mobile"><span class="grade-badge grade-average">62</span></td>
                        <td data-label="History" class="hide-mobile"><span class="grade-badge grade-average">55</span></td>
                        <td data-label="Average"><strong class="grade-average">55%</strong></td>
                        <td data-label="Grade"><span class="grade-badge grade-average">C</span></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="viewStudent(4)">
                                    👁️ View
                                </button>
                                <button class="btn btn-sm btn-warning d-none-mobile" onclick="helpStudent(4)">
                                    🆘 Help
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-wrapper">
            <div class="pagination-info">
                <p>Showing 1-4 of 156 students</p>
            </div>
            <div class="pagination-controls">
                <button class="btn btn-secondary" disabled>« Previous</button>
                <button class="btn btn-primary">1</button>
                <button class="btn btn-secondary">2</button>
                <button class="btn btn-secondary">3</button>
                <span>...</span>
                <button class="btn btn-secondary">39</button>
                <button class="btn btn-secondary">Next »</button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-6 col-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon">🎓</div>
            <div class="stat-info">
                <h4>156</h4>
                <p>Total Students</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card stat-success">
            <div class="stat-icon">⭐</div>
            <div class="stat-info">
                <h4>78%</h4>
                <p>Average Score</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon">📈</div>
            <div class="stat-info">
                <h4>12</h4>
                <p>Need Attention</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card stat-info">
            <div class="stat-icon">🏆</div>
            <div class="stat-info">
                <h4>23</h4>
                <p>Top Performers</p>
            </div>
        </div>
    </div>
</div>

<!-- Demo JavaScript -->
<script>
// Example functions for demo
function searchResults(event) {
    event.preventDefault();
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML = '<span class="loading"></span> Searching...';
    submitBtn.disabled = true;

    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        showToast('Search completed! Results updated.', 'success');
    }, 2000);

    return false;
}

function viewStudent(id) {
    showToast(`Viewing student details for ID: ${id}`, 'info');
}

function editStudent(id) {
    showToast(`Opening edit form for student ID: ${id}`, 'info');
}

function helpStudent(id) {
    showToast(`Generating help plan for student ID: ${id}`, 'warning');
}

// Demo page initialization
document.addEventListener('DOMContentLoaded', function() {
    showToast('📱 Mobile responsive page loaded successfully!', 'success', 3000);
});
</script>

<!-- Page-specific styles -->
<style>
.search-form {
    background: rgba(52, 152, 219, 0.05);
    padding: 1rem;
    border-radius: var(--border-radius);
}

.grade-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: white;
    font-weight: bold;
    font-size: 0.875rem;
}

.grade-excellent { background: #27ae60; }
.grade-good { background: #3498db; }
.grade-average { background: #f39c12; }
.grade-poor { background: #e74c3c; }

.action-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.pagination-controls {
    display: flex;
    gap: 0.25rem;
    align-items: center;
    flex-wrap: wrap;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.stat-icon {
    font-size: 2.5rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
}

.stat-primary .stat-icon { background: rgba(44, 62, 80, 0.1); }
.stat-success .stat-icon { background: rgba(39, 174, 96, 0.1); }
.stat-warning .stat-icon { background: rgba(243, 156, 18, 0.1); }
.stat-info .stat-icon { background: rgba(52, 152, 219, 0.1); }

.stat-info h4 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: var(--primary-color);
}

.stat-info p {
    margin: 0;
    color: var(--text-color);
    opacity: 0.8;
}

@media (max-width: 768px) {
    .pagination-wrapper {
        flex-direction: column;
        text-align: center;
    }

    .action-buttons {
        justify-content: center;
    }

    .pagination-controls {
        justify-content: center;
    }

    .stat-card {
        padding: 1rem;
    }

    .stat-icon {
        font-size: 2rem;
        width: 50px;
        height: 50px;
    }

    .stat-info h4 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .demo-actions {
        flex-direction: column;
    }

    .demo-actions .btn {
        width: 100%;
    }
}
</style>

<?php
// Include responsive footer (replaces your old closing tags)
include __DIR__ . '/views/layouts/responsive-footer.php';
?>

<!--
BEFORE: Your old page would end like this:
</body>
</html>

AFTER: The responsive footer handles all closing tags and adds:
- Mobile bottom navigation
- Footer content
- JavaScript initialization
- Dark mode toggle
- Scroll to top button
- PWA features
-->
