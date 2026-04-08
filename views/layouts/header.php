<?php
// Include helper functions
require_once __DIR__ . '/../../config/helper-functions.php';

/* ---------- Helpers (tiny, no behavior change) ---------- */

// Return " is-active" when the current request file matches $file exactly
if (!function_exists('is_here')) {
  function is_here(string $file): string {
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $here    = basename($reqPath ?: 'index.php') ?: 'index.php';
    return strcasecmp($here, $file) === 0 ? ' is-active' : '';
  }
}

// Derive a simple current page label (used by some headers)
if (!function_exists('current_page_label')) {
  function current_page_label(): string {
    if (!empty($GLOBALS['pageTitle'])) return (string)$GLOBALS['pageTitle'];
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $base = basename($path);
    if ($base === '' || $base === 'index.php') return 'Dashboard';
    $label = preg_replace('/\.php$/', '', $base);
    $label = ucwords(str_replace(['-', '_'], ' ', $label));
    return $label ?: 'Page';
  }
}

// Small HTML escape
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($pageTitle) ? e($pageTitle) : 'Uganda Results System'; ?></title>

  <!-- CSS -->
  <link rel="stylesheet" href="<?php echo asset_url('css/app.css'); ?>">
  <link rel="stylesheet" href="../../assets/css/app.css"><!-- fallback -->

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?php echo asset_url('img/favicon.svg'); ?>">

  <!-- Meta -->
  <meta name="description" content="<?php echo isset($pageDescription) ? e($pageDescription) : 'Uganda High School Results and Report Card Management System'; ?>">
  <meta name="keywords" content="Uganda, results, report card, high school, education, UCE, UACE">
  <meta name="author" content="Uganda Results System">
  <meta name="robots" content="<?php echo isset($robots) ? $robots : 'index, follow'; ?>">

  <style>
    /* keep your look; only add a subtle current-page indicator (no background) */
    .sidebar-nav .nav-link.is-active{
      background: #182542;
      color: #eaf2ff;
      border-left: 3px solid #9ee7ff;
    }

    /* your existing small header helpers (unchanged) */
    .app-header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-bottom:1px solid rgb(211,211,211);background:linear-gradient(180deg,#0f1626,#121c31)}
    .app-nav{display:flex;gap:8px;flex-wrap:wrap}
    .app-nav a{display:inline-flex;align-items:center;padding:8px 12px;border-radius:10px;text-decoration:none;color:#aabbd3;background:#0e1524;border:1px solid rgb(175,175,175)}
    .app-nav a:hover{color:#eaf2ff;border-color:#2b3b59}
    .current-chip{padding:6px 10px;border-radius:999px;background:#142238;color:#cfe6ff;border:1px solid rgb(204,204,204);font-size:12px;white-space:nowrap}
  </style>
</head>

<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">

  <!-- Loading indicator -->
  <div id="loading" class="loading-overlay" style="display:none;">
    <div class="loading-spinner"></div>
  </div>

  <?php if (is_logged_in()): ?>
  <!-- Dashboard Layout -->
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar" id="sidebar">
      <div class="sidebar-header">
        <a href="<?php echo base_url(); ?>" class="sidebar-logo">
          <div class="logo-icon">URS</div>
          <div>
            <div class="logo-text">Uganda Results</div>
            <div class="logo-subtitle">System</div>
          </div>
        </a>
      </div>

      <nav class="sidebar-nav">
        <?php
          $userRole = get_user_role();
        ?>

        <!-- Main Navigation -->
        <div class="nav-section">
          <h3 class="nav-section-title">Main</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="<?php echo get_dashboard_url(); ?>" class="nav-link<?php echo is_here('index.php'); ?>">
                <span class="nav-icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                </span>
                <span class="nav-text">Dashboard</span>
              </a>
            </li>

            <?php if ($userRole == 'STUDENT'): ?>
              <li class="nav-item">
                <a href="<?php echo base_url('student/profile.php'); ?>" class="nav-link<?php echo is_here('profile.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                  </span>
                  <span class="nav-text">My Profile</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('student/results.php'); ?>" class="nav-link<?php echo is_here('results.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                  </span>
                  <span class="nav-text">My Results</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('student/reports.php'); ?>" class="nav-link<?php echo is_here('reports.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-2V2h-2v2H9V2H7v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/></svg>
                  </span>
                  <span class="nav-text">Report Cards</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('student/attendance.php'); ?>" class="nav-link<?php echo is_here('attendance.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  </span>
                  <span class="nav-text">Attendance</span>
                </a>
              </li>

            <?php elseif (in_array($userRole, ['SCHOOL_ADMIN', 'STAFF'])): ?>
              <li class="nav-item">
                <a href="<?php echo base_url('admin/students.php'); ?>" class="nav-link<?php echo is_here('students.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A1.5 1.5 0 0 0 18.54 8H17c-.8 0-1.54.37-2.01 1l-2.41 3.22A.5.5 0 0 0 13 13h2v9h4zm-7.5-10.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5S11 9.17 11 10.5s.67 1.5 1.5 1.5zM5.5 6c1.11 0 2-.89 2-2s-.89-2-2-2-2 .89-2 2 .89 2 2 2zm2 16v-7H9V9.5C9 8.12 7.88 7 6.5 7S4 8.12 4 9.5V15H5.5v7h2z"/></svg>
                  </span>
                  <span class="nav-text">Students</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('admin/classes.php'); ?>" class="nav-link<?php echo is_here('classes.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9l-11-6zM18.82 9L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>
                  </span>
                  <span class="nav-text">Classes</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('admin/subjects.php'); ?>" class="nav-link<?php echo is_here('subjects.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                  </span>
                  <span class="nav-text">Subjects</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('admin/results.php'); ?>" class="nav-link<?php echo is_here('results.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  </span>
                  <span class="nav-text">Results</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('admin/reports.php'); ?>" class="nav-link<?php echo is_here('reports.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                  </span>
                  <span class="nav-text">Reports</span>
                </a>
              </li>

            <?php elseif ($userRole == 'SUPER_ADMIN'): ?>
              <li class="nav-item">
                <a href="<?php echo base_url('super/schools.php'); ?>" class="nav-link<?php echo is_here('schools.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9l-11-6z"/></svg>
                  </span>
                  <span class="nav-text">Schools</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('super/users.php'); ?>" class="nav-link<?php echo is_here('users.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  </span>
                  <span class="nav-text">Users</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('super/analytics.php'); ?>" class="nav-link<?php echo is_here('analytics.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3v18h18v-2H5V3H3zm4 14h2v-6H7v6zm4 0h2V9h-2v8zm4 0h2v-4h-2v4z"/></svg>
                  </span>
                  <span class="nav-text">Analytics</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo base_url('super/system.php'); ?>" class="nav-link<?php echo is_here('system.php'); ?>">
                  <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L1 9l4 2.18v6L12 21l7-3.82v-6L23 9l-11-6z"/></svg>
                  </span>
                  <span class="nav-text">System</span>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Account Section -->
        <div class="nav-section">
          <h3 class="nav-section-title">Account</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="<?php echo base_url('auth/change-password.php'); ?>" class="nav-link<?php echo is_here('change-password.php'); ?>">
                <span class="nav-icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17a2 2 0 0 0 2-2 2 2 0 0 0-2-2 2 2 0 0 0-2 2 2 2 0 0 0 2 2m6-9a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V10a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5 5 5 0 0 1 5 5v2h1m-6-5a3 3 0 0 0-3 3v2h6V6a3 3 0 0 0-3-3z"/></svg>
                </span>
                <span class="nav-text">Change Password</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo base_url('auth/logout.php'); ?>" class="nav-link<?php echo is_here('logout.php'); ?>">
                <span class="nav-icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                </span>
                <span class="nav-text">Logout</span>
              </a>
            </li>
          </ul>
        </div>
      </nav>

      <!-- Settings Footer -->
      <div class="sidebar-settings">
        <div class="settings-item">
          <span>2FA Settings</span>
          <div class="toggle-switch" id="twoFactorToggle"></div>
        </div>
      </div>
    </aside>

    <!-- Header -->
    <header class="dashboard-header" id="header">
      <div class="header-content">
        <div class="header-left">
          <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 12h18m-9 6H3m15-12H3"/></svg>
          </button>
          <!-- Optional page chip -->
          <span class="current-chip"><?php echo e(current_page_label()); ?></span>
        </div>

        <div class="header-right">
          <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></div>
            <div class="user-info">
              <div class="user-name"><?php echo e($_SESSION['user_name'] ?? 'User'); ?></div>
              <div class="user-role"><?php echo ucwords(str_replace('_',' ', strtolower($userRole))); ?></div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-main" id="mainContent">
  <?php else: ?>
    <!-- Non-dashboard content for login pages -->
  <?php endif; ?>
