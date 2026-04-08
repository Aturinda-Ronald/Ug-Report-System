<?php
declare(strict_types=1);

// Include configuration
require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(base_url());
}

try {
    $db = Database::getInstance();
    $user_id = get_user_id();
    $school_id = get_school_id();

    // Log the logout activity
    if ($user_id) {
        $stmt = $db->prepare("
            INSERT INTO activity_logs (school_id, user_id, action, description, ip_address, user_agent)
            VALUES (?, ?, 'LOGOUT', ?, ?, ?)
        ");
        $stmt->execute([
            $school_id,
            $user_id,
            'User logged out',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
} catch (Exception $e) {
    // Log error but don't prevent logout
    error_log('Logout logging error: ' . $e->getMessage());
}

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie(
        'remember_token',
        '',
        time() - 3600,
        '/',
        '',
        isset($_SERVER['HTTPS']),
        true
    );
}

// Destroy session
session_unset();
session_destroy();

// Regenerate session ID for security
session_start();
session_regenerate_id(true);

// Set logout success message
$_SESSION['success_message'] = 'You have been successfully logged out.';

// Redirect to home page
redirect(base_url());
