<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Student Model
 * Handles student records and related operations
 */
class Student extends BaseModel
{
    protected string $table = 'students';
    protected bool $hasSchoolId = true;

    protected array $fillable = [
        'school_id', 'user_id', 'index_no', 'first_name', 'last_name', 'other_names',
        'gender', 'date_of_birth', 'class_id', 'stream_id', 'admission_date',
        'graduation_date', 'status', 'guardian_name', 'guardian_phone', 'guardian_email',
        'address', 'photo_url', 'medical_info', 'notes'
    ];

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_GRADUATED = 'GRADUATED';
    public const STATUS_TRANSFERRED = 'TRANSFERRED';
    public const STATUS_DROPPED = 'DROPPED';

    /**
     * Gender constants
     */
    public const GENDER_MALE = 'M';
    public const GENDER_FEMALE = 'F';

    /**
     * Find student by index number
     */
    public function findByIndexNo(string $indexNo, ?int $schoolId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE index_no = ?";
        $params = [$indexNo];

        if ($schoolId !== null) {
            $sql .= " AND school_id = ?";
            $params[] = $schoolId;
        } elseif ($this->getCurrentSchoolId()) {
            $sql .= " AND school_id = ?";
            $params[] = $this->getCurrentSchoolId();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Find student with full details (class, stream, school)
     */
    public function findWithDetails(int $id): ?array
    {
        $sql = "
            SELECT s.*, c.name as class_name, st.name as stream_name,
                   sc.name as school_name, u.email, u.last_login
            FROM {$this->table} s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN streams st ON s.stream_id = st.id
            LEFT JOIN schools sc ON s.school_id = sc.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ";
        $params = [$id];

        // Apply school filter if needed
        if ($this->getCurrentSchoolId()) {
            $sql .= " AND s.school_id = ?";
            $params[] = $this->getCurrentSchoolId();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Find students by class and stream
     */
    public function findByClassStream(int $classId, ?int $streamId = null): array
    {
        $sql = "
            SELECT s.*, c.name as class_name, st.name as stream_name
            FROM {$this->table} s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN streams st ON s.stream_id = st.id
            WHERE s.class_id = ? AND s.status = ?
        ";
        $params = [$classId, self::STATUS_ACTIVE];

        if ($streamId !== null) {
            $sql .= " AND s.stream_id = ?";
            $params[] = $streamId;
        }

        // Apply school filter
        [$sql, $params] = $this->applySchoolFilter($sql, $params);

        $sql .= " ORDER BY s.first_name, s.last_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Get students with their login credentials
     */
    public function findWithCredentials(string $schoolName, string $indexNo): ?array
    {
        $sql = "
            SELECT s.*, u.password_hash, u.id as user_id, u.last_login, u.is_active as user_active,
                   sc.name as school_name, sc.id as school_id, c.name as class_name, st.name as stream_name
            FROM {$this->table} s
            INNER JOIN schools sc ON s.school_id = sc.id
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN streams st ON s.stream_id = st.id
            WHERE sc.name = ? AND s.index_no = ? AND s.status = ? AND sc.is_active = 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$schoolName, $indexNo, self::STATUS_ACTIVE]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Create student with validation
     */
    public function createStudent(array $data): int
    {
        // Validate required fields
        $required = ['index_no', 'first_name', 'last_name', 'gender'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field {$field} is required");
            }
        }

        // Validate index number uniqueness within school
        $schoolId = $data['school_id'] ?? $this->getCurrentSchoolId();
        if ($this->findByIndexNo($data['index_no'], $schoolId)) {
            throw new InvalidArgumentException("Index number already exists in this school");
        }

        // Validate gender
        if (!in_array($data['gender'], [self::GENDER_MALE, self::GENDER_FEMALE])) {
            throw new InvalidArgumentException("Invalid gender value");
        }

        // Set default status
        if (empty($data['status'])) {
            $data['status'] = self::STATUS_ACTIVE;
        }

        return $this->create($data);
    }

    /**
     * Get student's full name
     */
    public function getFullName(array $student): string
    {
        $name = trim($student['first_name'] . ' ' . $student['last_name']);
        if (!empty($student['other_names'])) {
            $name .= ' ' . $student['other_names'];
        }
        return $name;
    }

    /**
     * Get student's age
     */
    public function getAge(array $student): ?int
    {
        if (empty($student['date_of_birth'])) {
            return null;
        }

        $birthDate = new DateTime($student['date_of_birth']);
        $today = new DateTime();
        return $birthDate->diff($today)->y;
    }

    /**
     * Search students
     */
    public function search(string $query, ?int $classId = null, ?int $streamId = null): array
    {
        $sql = "
            SELECT s.*, c.name as class_name, st.name as stream_name
            FROM {$this->table} s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN streams st ON s.stream_id = st.id
            WHERE s.status = ?
            AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.index_no LIKE ?)
        ";
        $params = [self::STATUS_ACTIVE, "%{$query}%", "%{$query}%", "%{$query}%"];

        if ($classId !== null) {
            $sql .= " AND s.class_id = ?";
            $params[] = $classId;
        }

        if ($streamId !== null) {
            $sql .= " AND s.stream_id = ?";
            $params[] = $streamId;
        }

        // Apply school filter
        [$sql, $params] = $this->applySchoolFilter($sql, $params);

        $sql .= " ORDER BY s.first_name, s.last_name LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Get student count by status
     */
    public function countByStatus(string $status = self::STATUS_ACTIVE): int
    {
        return $this->count(['status' => $status]);
    }

    /**
     * Get students by gender
     */
    public function countByGender(): array
    {
        $sql = "
            SELECT gender, COUNT(*) as count
            FROM {$this->table}
            WHERE status = ?
        ";
        $params = [self::STATUS_ACTIVE];

        [$sql, $params] = $this->applySchoolFilter($sql, $params);
        $sql .= " GROUP BY gender";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetchAll();

        // Format result
        $counts = [self::GENDER_MALE => 0, self::GENDER_FEMALE => 0];
        foreach ($result as $row) {
            $counts[$row['gender']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Get students enrolled in a specific academic year
     */
    public function findByAcademicYear(int $academicYearId): array
    {
        $sql = "
            SELECT DISTINCT s.*, c.name as class_name, st.name as stream_name
            FROM {$this->table} s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN streams st ON s.stream_id = st.id
            INNER JOIN student_subjects ss ON s.id = ss.student_id
            WHERE ss.academic_year_id = ? AND s.status = ?
        ";
        $params = [$academicYearId, self::STATUS_ACTIVE];

        [$sql, $params] = $this->applySchoolFilter($sql, $params);
        $sql .= " ORDER BY c.year_group, s.first_name, s.last_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Update student class/stream
     */
    public function updateClassStream(int $studentId, int $classId, ?int $streamId = null): bool
    {
        $data = ['class_id' => $classId];
        if ($streamId !== null) {
            $data['stream_id'] = $streamId;
        }

        return $this->update($studentId, $data);
    }

    /**
     * Graduate students
     */
    public function graduateStudents(array $studentIds, string $graduationDate): bool
    {
        $this->beginTransaction();

        try {
            $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
            $sql = "
                UPDATE {$this->table}
                SET status = ?, graduation_date = ?, updated_at = NOW()
                WHERE id IN ({$placeholders})
            ";
            $params = [self::STATUS_GRADUATED, $graduationDate, ...$studentIds];

            // Apply school filter
            [$sql, $params] = $this->applySchoolFilter($sql, $params);

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
