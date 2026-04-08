<?php
declare(strict_types=1);

/**
 * Uganda High School Results & Report Card System
 * Main Configuration File - PHP 8.0+ Compatible
 */

// Temporary error debugging (remove in production)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Africa/Kampala');

// Application constants
define('APP_NAME', 'Uganda Results System By ');
define('APP_VERSION', '1.0.0');
// Dynamic APP_URL based on current request
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = '';
if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/reports_system/') !== false) {
    $basePath = '/reports_system';
}
define('APP_URL', $protocol . '://' . $host . $basePath);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'uganda_results');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty for XAMPP default

// Check if database constants are defined
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// File upload settings
define('MAX_FILE_SIZE', 1048576); // 1MB
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('LOGO_PATH', __DIR__ . '/../assets/img/logos/');
define('AVATAR_PATH', __DIR__ . '/../assets/img/avatars/');
define('EXPORT_PATH', __DIR__ . '/../assets/exports/');

// Security settings
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_NAME', '_token');
define('PASSWORD_MIN_LENGTH', 6);

// Pagination settings
define('RECORDS_PER_PAGE', 50);
define('STUDENTS_PER_PAGE', 25);

// Application paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', ROOT_PATH . '/views');

// URL helpers
function base_url(string $path = ''): string {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? '80';

    // Detect VirtualHost setup (port 8082) vs normal localhost access
    $isVirtualHost = ($port == '8082' || strpos($host, ':8082') !== false);
    $isLocalhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

    $baseUrl = '';

    if ($isVirtualHost) {
        // VirtualHost on port 8082: system served as root, clean URLs
        $baseUrl = $protocol . '://' . $host;
    } elseif ($isLocalhost) {
        // Normal localhost: include /reports_system/public path
        $baseUrl = $protocol . '://' . $host . '/reports_system/public';
    } else {
        // Fallback for other scenarios
        $baseUrl = $protocol . '://' . $host;
    }

    if ($path) {
        $baseUrl .= '/' . ltrim($path, '/');
    }

    return $baseUrl;
}

function asset_url(string $path): string {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? '80';

    // Use the same VirtualHost detection logic as base_url
    $isVirtualHost = ($port == '8082' || strpos($host, ':8082') !== false);
    $isLocalhost = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

    $baseUrl = '';

    if ($isVirtualHost) {
        // VirtualHost on port 8082: assets served from root
        $baseUrl = $protocol . '://' . $host;
    } elseif ($isLocalhost) {
        // Normal localhost: include /reports_system path
        $baseUrl = $protocol . '://' . $host . '/reports_system';
    } else {
        // Fallback for other scenarios
        $baseUrl = $protocol . '://' . $host;
    }

    return $baseUrl . '/assets/' . ltrim($path, '/');
}

function redirect(string $url, int $statusCode = 302): void {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

// Ensure upload directories exist
$uploadDirs = [UPLOAD_PATH, LOGO_PATH, AVATAR_PATH, EXPORT_PATH];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log("Failed to create directory: $dir");
        }
    }
}

// Include additional config files
$constantsFile = __DIR__ . '/constants.php';
if (file_exists($constantsFile)) {
    require_once $constantsFile;
}

// Autoloader for classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);

    // Check in different directories
    $directories = [
        APP_PATH . '/Controllers/',
        APP_PATH . '/Models/',
        APP_PATH . '/Services/',
        APP_PATH . '/Policies/',
        APP_PATH . '/Helpers/',
    ];

    foreach ($directories as $dir) {
        $file = $dir . $classPath . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // Try direct class name in app directory
    $file = APP_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Database connection singleton
class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true
                ]);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                die('Database connection failed. Please check your configuration.');
            }
        }

        return self::$instance;
    }
}

// CSRF Protection
function generate_csrf_token(): string {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrf_token_input(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generate_csrf_token() . '">';
}

// Session helpers
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function get_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function get_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function get_school_id(): ?int {
    return $_SESSION['school_id'] ?? null;
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect('/auth/login-admin.php');
    }
}

function require_role(string ...$roles): void {
    require_login();

    $userRole = get_user_role();
    if (!in_array($userRole, $roles)) {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

// Validation helpers
function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitize_input(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validate_file_upload(array $file, array $allowedTypes = ['image/jpeg', 'image/png'], int $maxSize = MAX_FILE_SIZE): array {
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed with error code: ' . $file['error'];
        return $errors;
    }

    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds maximum allowed size of ' . number_format($maxSize / 1024 / 1024, 1) . 'MB';
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
        }
    }

    return $errors;
}

// Utility functions
function format_date(string $date, string $format = 'j M Y'): string {
    return date($format, strtotime($date));
}

function format_grade(float $mark, string $gradeCode): string {
    return number_format($mark, 1) . '% (' . $gradeCode . ')';
}

function ordinal(int $number): string {
    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return $number . 'th';
    }
    return $number . $ends[$number % 10];
}

// Error handling
function handle_error(string $message, string $file = '', int $line = 0): void {
    error_log("Error: $message in $file on line $line");

    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<strong>Error:</strong> $message";
    if ($file && $line) {
        echo "<br><small>File: $file, Line: $line</small>";
    }
    echo "</div>";
}

// Set custom error handler
set_error_handler(function($severity, $message, $file, $line) {
    handle_error($message, $file, $line);
    return true;
});

// Test database connection on load
try {
    $testDb = Database::getInstance();
    echo "<!-- Database connection successful -->\n";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<strong>Database Connection Error:</strong><br>";
    echo "Please check your database configuration.<br>";
    echo "<small>Error: " . htmlspecialchars($e->getMessage()) . "</small>";
    echo "</div>";
}
