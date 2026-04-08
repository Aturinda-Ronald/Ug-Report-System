<?php
declare(strict_types=1);

/**
 * Base Model Class
 * Provides common database operations with multi-tenant support
 */
abstract class BaseModel
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $hasSchoolId = true;
    protected array $fillable = [];
    protected array $dates = ['created_at', 'updated_at'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get current school ID from session
     */
    protected function getCurrentSchoolId(): ?int
    {
        return get_school_id();
    }

    /**
     * Apply school filter to query if needed
     */
    protected function applySchoolFilter(string $sql, array $params = []): array
    {
        if ($this->hasSchoolId && $this->getCurrentSchoolId()) {
            if (stripos($sql, 'WHERE') !== false) {
                $sql .= ' AND school_id = ?';
            } else {
                $sql .= ' WHERE school_id = ?';
            }
            $params[] = $this->getCurrentSchoolId();
        }
        return [$sql, $params];
    }

    /**
     * Find a record by ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $params = [$id];

        [$sql, $params] = $this->applySchoolFilter($sql, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Find all records
     */
    public function findAll(array $conditions = [], string $orderBy = '', int $limit = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        // Add conditions
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // Apply school filter
        [$sql, $params] = $this->applySchoolFilter($sql, $params);

        // Add order by
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        // Add limit
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Create a new record
     */
    public function create(array $data): int
    {
        // Add school_id if required
        if ($this->hasSchoolId && $this->getCurrentSchoolId() && !isset($data['school_id'])) {
            $data['school_id'] = $this->getCurrentSchoolId();
        }

        // Filter data to only include fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        // Add timestamps
        if (in_array('created_at', $this->dates)) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (in_array('updated_at', $this->dates)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';

        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool
    {
        // Filter data to only include fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        // Add updated timestamp
        if (in_array('updated_at', $this->dates)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = array_keys($data);
        $setClauses = array_map(fn($field) => "{$field} = ?", $fields);

        $sql = "UPDATE {$this->table} SET " . implode(',', $setClauses) . " WHERE {$this->primaryKey} = ?";
        $params = array_values($data);
        $params[] = $id;

        // Apply school filter
        [$sql, $params] = $this->applySchoolFilter($sql, $params);

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a record
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $params = [$id];

        // Apply school filter
        [$sql, $params] = $this->applySchoolFilter($sql, $params);

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Count records
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        // Add conditions
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // Apply school filter
        [$sql, $params] = $this->applySchoolFilter($sql, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int)($result['count'] ?? 0);
    }

    /**
     * Execute raw SQL query
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute raw SQL statement (INSERT, UPDATE, DELETE)
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->db->rollback();
    }

    /**
     * Check if a record exists
     */
    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $page = 1, int $perPage = 20, array $conditions = [], string $orderBy = ''): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $total = $this->count($conditions);

        // Get records for current page
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        // Add conditions
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $field => $value) {
                $whereClauses[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // Apply school filter
        [$sql, $params] = $this->applySchoolFilter($sql, $params);

        // Add order by
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        // Add pagination
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int)ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
}
