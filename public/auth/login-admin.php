<?php
declare(strict_types=1);

// Include configuration and bootstrap
require_once __DIR__ . '/../../config/config.php';

// If already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    $role = get_user_role();
    switch ($role) {
        case 'SUPER_ADMIN':
            redirect(base_url('super/'));
            break;
        case 'SCHOOL_ADMIN':
        case 'STAFF':
            redirect(base_url('admin/'));
            break;
        default:
            session_destroy();
            break;
    }
}

// Initialize variables
$errors = [];
$old_input = [];

// Handle POST request (login attempt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['_token'] ?? '')) {
        $errors['login'] = 'Invalid security token. Please try again.';
    } else {
        // Get and validate input
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Store old input for repopulation
        $old_input = ['email' => $email];

        // Validate inputs
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!validate_email($email)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        }

        // If no validation errors, attempt authentication
        if (empty($errors)) {
            try {
                $db = Database::getInstance();

                // Find user by email with school information
                $stmt = $db->prepare("
                    SELECT u.*, s.name as school_name, s.id as school_id
                    FROM users u
                    LEFT JOIN schools s ON u.school_id = s.id
                    WHERE u.email = ? AND u.role IN ('SUPER_ADMIN', 'SCHOOL_ADMIN', 'STAFF') AND u.is_active = 1
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Password is correct, create session
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['school_id'] = $user['school_id'] ? (int)$user['school_id'] : null;
                    $_SESSION['school_name'] = $user['school_name'] ?? null;
                    $_SESSION['last_activity'] = time();

                    // Update last login
                    $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);

                    // Log the login activity
                    $logStmt = $db->prepare("
                        INSERT INTO activity_logs (school_id, user_id, action, description, ip_address, user_agent)
                        VALUES (?, ?, 'LOGIN', ?, ?, ?)
                    ");
                    $logStmt->execute([
                        $user['school_id'],
                        $user['id'],
                        'User logged in via admin portal',
                        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);

                    // Handle remember me functionality
                    if ($remember) {
                        // Set a secure remember token (simplified for demo)
                        setcookie(
                            'remember_token',
                            base64_encode($user['email']),
                            time() + (86400 * 30), // 30 days
                            '/',
                            '',
                            isset($_SERVER['HTTPS']),
                            true
                        );
                    }

                    // Redirect based on role
                    switch ($user['role']) {
                        case 'SUPER_ADMIN':
                            redirect(base_url('super/'));
                            break;
                        case 'SCHOOL_ADMIN':
                        case 'STAFF':
                            redirect(base_url('admin/'));
                            break;
                        default:
                            redirect(base_url());
                            break;
                    }
                } else {
                    $errors['login'] = 'Invalid email or password. Please check your credentials and try again.';

                    // Log failed login attempt
                    if ($user) {
                        $logStmt = $db->prepare("
                            INSERT INTO activity_logs (school_id, user_id, action, description, ip_address, user_agent)
                            VALUES (?, ?, 'LOGIN_FAILED', ?, ?, ?)
                        ");
                        $logStmt->execute([
                            $user['school_id'],
                            $user['id'],
                            'Failed login attempt - incorrect password',
                            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                            $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                $errors['login'] = 'A system error occurred. Please try again later.';
            }
        }
    }
}

// Include the view
include __DIR__ . '/../../views/auth/admin-login.php';
