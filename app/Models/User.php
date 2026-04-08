<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * User Model
 * Handles user authentication and management
 */
class User extends BaseModel
{
    protected string $table = 'users';
    protected bool $hasSchoolId = false; // Users can be global (super admin) or school-specific

    protected array $fillable = [
        'school_id', 'email', 'password_hash', 'first_name', 'last_name',
        'role', 'phone', 'avatar_url', 'is_active'
    ];

    /**
     * Role constants
     */
    public const ROLE_SUPER_ADMIN = 'SUPER_ADMIN';
    public const ROLE_SCHOOL_ADMIN = 'SCHOOL_ADMIN';
    public const ROLE_STAFF = 'STAFF';
    public const ROLE_STUDENT = 'STUDENT';

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Find user by email with school information
     */
    public function findByEmailWithSchool(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, s.name as school_name, s.id as school_id
            FROM {$this->table} u
            LEFT JOIN schools s ON u.school_id = s.id
            WHERE u.email = ? AND u.is_active = 1
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Create user with hashed password
     */
    public function createUser(array $data): int
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = $this->hashPassword($data['password']);
            unset($data['password']);
        }

        // Validate required fields
        $required = ['email', 'password_hash', 'first_name', 'last_name', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field {$field} is required");
            }
        }

        // Validate email uniqueness
        if ($this->findByEmail($data['email'])) {
            throw new InvalidArgumentException("Email already exists");
        }

        // Validate role
        $validRoles = [self::ROLE_SUPER_ADMIN, self::ROLE_SCHOOL_ADMIN, self::ROLE_STAFF, self::ROLE_STUDENT];
        if (!in_array($data['role'], $validRoles)) {
            throw new InvalidArgumentException("Invalid role");
        }

        return $this->create($data);
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = $this->hashPassword($newPassword);

        $stmt = $this->db->prepare("UPDATE {$this->table} SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Verify user password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    /**
     * Find users by role
     */
    public function findByRole(string $role, ?int $schoolId = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE role = ? AND is_active = 1";
        $params = [$role];

        if ($schoolId !== null) {
            $sql .= " AND school_id = ?";
            $params[] = $schoolId;
        }

        $sql .= " ORDER BY first_name, last_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Find users for a specific school
     */
    public function findBySchool(int $schoolId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE school_id = ? AND is_active = 1
            ORDER BY role, first_name, last_name
        ");
        $stmt->execute([$schoolId]);

        return $stmt->fetchAll();
    }

    /**
     * Get user's full name
     */
    public function getFullName(array $user): string
    {
        return trim($user['first_name'] . ' ' . $user['last_name']);
    }

    /**
     * Check if user has role
     */
    public function hasRole(array $user, string $role): bool
    {
        return $user['role'] === $role;
    }

    /**
     * Check if user is admin (school admin or super admin)
     */
    public function isAdmin(array $user): bool
    {
        return in_array($user['role'], [self::ROLE_SUPER_ADMIN, self::ROLE_SCHOOL_ADMIN]);
    }

    /**
     * Check if user can access school
     */
    public function canAccessSchool(array $user, int $schoolId): bool
    {
        // Super admin can access any school
        if ($user['role'] === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        // Other users can only access their own school
        return (int)$user['school_id'] === $schoolId;
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET password_reset_token = ?, password_reset_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$token, $expires, $userId]);

        return $token;
    }

    /**
     * Verify password reset token
     */
    public function verifyPasswordResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE password_reset_token = ?
            AND password_reset_expires > NOW()
            AND is_active = 1
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Clear password reset token
     */
    public function clearPasswordResetToken(int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET password_reset_token = NULL, password_reset_expires = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    /**
     * Update last login time
     */
    public function updateLastLogin(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Get active user count by role
     */
    public function countByRole(string $role, ?int $schoolId = null): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE role = ? AND is_active = 1";
        $params = [$role];

        if ($schoolId !== null) {
            $sql .= " AND school_id = ?";
            $params[] = $schoolId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int)($result['count'] ?? 0);
    }

    /**
     * Search users
     */
    public function search(string $query, ?int $schoolId = null, ?string $role = null): array
    {
        $sql = "
            SELECT u.*, s.name as school_name
            FROM {$this->table} u
            LEFT JOIN schools s ON u.school_id = s.id
            WHERE u.is_active = 1
            AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)
        ";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];

        if ($schoolId !== null) {
            $sql .= " AND u.school_id = ?";
            $params[] = $schoolId;
        }

        if ($role !== null) {
            $sql .= " AND u.role = ?";
            $params[] = $role;
        }

        $sql .= " ORDER BY u.first_name, u.last_name LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
