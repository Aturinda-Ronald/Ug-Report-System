<?php
/**
 * Responsive Header Template for Uganda Results System
 * Includes hamburger menu and mobile-friendly navigation
 */

// Include config if not already included
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Get current user info
$currentUser = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['first_name'] ?? 'User';
$schoolName = $_SESSION['school_name'] ?? 'Uganda Results System';

// Current page detection for active nav items
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

// Navigation items based on user role
$navItems = [];

if (!$currentUser) {
    // Public navigation
    $navItems = [
        ['text' => 'Home', 'href' => base_url(''), 'icon' => '🏠'],
        ['text' => 'Admin Login', 'href' => base_url('auth/login-admin.php'), 'icon' => '👨‍💼'],
        ['text' => 'Student Login', 'href' => base_url('auth/login-student.php'), 'icon' => '🎓'],
        ['text' => 'Schools', 'href' => base_url('auth/schools.php'), 'icon' => '🏫']
    ];
} else {
    // Authenticated navigation based on role
    switch ($userRole) {
        case 'SUPER_ADMIN':
            $navItems = [
                ['text' => 'Dashboard', 'href' => base_url('super/'), 'icon' => '📋'],
                ['text' => 'Schools', 'href' => base_url('super/schools.php'), 'icon' => '🏫'],
                ['text' => 'Analytics', 'href' => base_url('super/analytics.php'), 'icon' => '📊'],
                ['text' => 'Users', 'href' => base_url('super/users.php'), 'icon' => '👥'],
                ['text' => 'Settings', 'href' => base_url('super/settings.php'), 'icon' => '⚙️']
            ];
            break;

        case 'SCHOOL_ADMIN':
        case 'STAFF':
            $navItems = [
                ['text' => 'Dashboard', 'href' => base_url('admin/'), 'icon' => '📋'],
                ['text' => 'Students', 'href' => base_url('admin/students.php'), 'icon' => '🎓'],
                ['text' => 'Classes', 'href' => base_url('admin/classes.php'), 'icon' => '📚'],
                ['text' => 'Subjects', 'href' => base_url('admin/subjects.php'), 'icon' => '📖'],
                ['text' => 'Results', 'href' => base_url('admin/results.php'), 'icon' => '📝'],
                ['text' => 'Reports', 'href' => base_url('admin/reports.php'), 'icon' => '📊'],
                ['text' => 'Users', 'href' => base_url('admin/users.php'), 'icon' => '👤'],
                ['text' => 'Settings', 'href' => base_url('admin/settings.php'), 'icon' => '⚙️']
            ];
            break;

        case 'STUDENT':
            $navItems = [
                ['text' => 'Dashboard', 'href' => base_url('student/'), 'icon' => '📋'],
                ['text' => 'My Results', 'href' => base_url('student/results.php'), 'icon' => '📝'],
                ['text' => 'Report Cards', 'href' => base_url('student/reports.php'), 'icon' => '📄'],
                ['text' => 'Profile', 'href' => base_url('student/profile.php'), 'icon' => '👤']
            ];
            break;
    }

    // Add logout for authenticated users
    $navItems[] = ['text' => 'Logout', 'href' => base_url('auth/logout.php'), 'icon' => '🚪'];
}

// Function to check if nav item is active
function isNavItemActive($href, $currentPath) {
    $href = parse_url($href, PHP_URL_PATH);
    return strpos($currentPath, basename($href)) !== false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Uganda High School Results and Report Card Management System">
    <meta name="author" content="Aturinda Ronald">

    <!-- Mobile theme color -->
    <meta name="theme-color" content="#2c3e50">
    <meta name="msapplication-navbutton-color" content="#2c3e50">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- PWA capabilities -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <title><?php echo $pageTitle ?? 'Uganda Results System'; ?></title>

    <!-- Responsive CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/mobile-responsive.css'); ?>">

    <!-- Additional page-specific CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo asset_url($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset_url('images/favicon.ico'); ?>">

    <!-- Preload critical resources -->
    <link rel="preload" href="<?php echo asset_url('css/mobile-responsive.css'); ?>" as="style">
    <link rel="preload" href="<?php echo asset_url('js/mobile-responsive.js'); ?>" as="script">
</head>
<body class="<?php echo $bodyClass ?? ''; ?>">

<!-- Mobile Header -->
<header class="mobile-header">
    <div class="header-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <?php if (isset($_SESSION['school_logo']) && $_SESSION['school_logo']): ?>
                <img src="<?php echo asset_url('uploads/logos/' . $_SESSION['school_logo']); ?>"
                     alt="School Logo" class="logo">
            <?php endif; ?>
            <div>
                <h1><?php echo htmlspecialchars($schoolName); ?></h1>
                <?php if ($currentUser): ?>
                    <small>Welcome, <?php echo htmlspecialchars($userName); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <!-- Desktop Navigation -->
        <nav class="desktop-nav">
            <?php foreach ($navItems as $item): ?>
                <a href="<?php echo $item['href']; ?>"
                   class="<?php echo isNavItemActive($item['href'], $currentPath) ? 'active' : ''; ?>">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <?php echo htmlspecialchars($item['text']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Hamburger Menu Button -->
        <button class="hamburger" aria-label="Toggle navigation menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<!-- Mobile Navigation -->
<nav class="mobile-nav">
    <ul class="mobile-nav-list">
        <?php foreach ($navItems as $item): ?>
            <li>
                <a href="<?php echo $item['href']; ?>"
                   class="<?php echo isNavItemActive($item['href'], $currentPath) ? 'active' : ''; ?>">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <?php echo htmlspecialchars($item['text']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- Navigation Overlay -->
<div class="nav-overlay"></div>

<!-- Main Content Wrapper -->
<main class="main-content">
    <div class="container">

        <!-- Page Header (if specified) -->
        <?php if (isset($pageHeader) && $pageHeader): ?>
            <div class="page-header fade-in">
                <h1><?php echo htmlspecialchars($pageHeader['title'] ?? ''); ?></h1>
                <?php if (isset($pageHeader['description'])): ?>
                    <p><?php echo htmlspecialchars($pageHeader['description']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb Navigation -->
        <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
            <nav class="breadcrumb fade-in" style="margin: 1rem 0;">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($index === count($breadcrumbs) - 1): ?>
                        <span class="breadcrumb-current"><?php echo htmlspecialchars($crumb['text']); ?></span>
                    <?php else: ?>
                        <a href="<?php echo $crumb['href']; ?>"><?php echo htmlspecialchars($crumb['text']); ?></a>
                        <span class="breadcrumb-separator"> › </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> fade-in" id="flash-message">
                <?php
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                ?>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <!-- Content starts here -->
        <div class="content-wrapper fade-in">

<!-- CSS for additional responsive elements -->
<style>
.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    padding: 0.5rem 1rem;
    background: rgba(255,255,255,0.9);
    border-radius: var(--border-radius);
    font-size: 0.9rem;
    box-shadow: var(--shadow);
}

.breadcrumb a {
    color: var(--secondary-color);
    text-decoration: none;
    transition: var(--transition);
}

.breadcrumb a:hover {
    color: var(--primary-color);
    text-decoration: underline;
}

.breadcrumb-current {
    color: var(--text-color);
    font-weight: 600;
}

.breadcrumb-separator {
    margin: 0 0.5rem;
    color: var(--border-color);
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: var(--border-radius);
    position: relative;
    box-shadow: var(--shadow);
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #bee5eb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #c3e6cb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffeaa7;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #f5c6cb;
}

.alert-close {
    position: absolute;
    top: 0.5rem;
    right: 1rem;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    transition: var(--transition);
}

.alert-close:hover {
    opacity: 1;
}

@media (max-width: 480px) {
    .breadcrumb {
        font-size: 0.8rem;
        padding: 0.5rem;
    }

    .breadcrumb-separator {
        margin: 0 0.25rem;
    }
}
</style>
