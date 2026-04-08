<?php
// Helper functions for Uganda Results System

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('get_user_role')) {
    function get_user_role(): ?string {
        return $_SESSION['user_role'] ?? null;
    }
}

if (!function_exists('get_dashboard_url')) {
    function get_dashboard_url(): string {
        $role = get_user_role();
        switch ($role) {
            case 'SUPER_ADMIN':
                return base_url('super/');
            case 'SCHOOL_ADMIN':
            case 'STAFF':
                return base_url('admin/');
            case 'STUDENT':
                return base_url('student/');
            default:
                return base_url();
        }
    }
}

if (!function_exists('require_role')) {
    function require_role(...$roles): void {
        if (!is_logged_in()) {
            redirect(base_url('auth/login-admin.php'));
        }

        $userRole = get_user_role();
        if (!in_array($userRole, $roles)) {
            redirect(base_url('error/403.php'));
        }
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validate_email')) {
    function validate_email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Database class (simplified)
if (!class_exists('Database')) {
    class Database {
        private static $instance = null;
        private $connection;

        private function __construct() {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new Exception('Database connection failed');
            }
        }

        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance->connection;
        }

        public function prepare($sql) {
            return $this->connection->prepare($sql);
        }

        public function query($sql) {
            return $this->connection->query($sql);
        }

        public function lastInsertId() {
            return $this->connection->lastInsertId();
        }
    }
}
?>
