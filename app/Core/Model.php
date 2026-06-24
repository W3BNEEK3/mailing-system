<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Model
 *
 * Base class for all database models. Provides common CRUD operations
 * using PDO prepared statements.
 *
 * Each model that extends this class must define:
 *   protected static string $table = 'table_name';
 *   protected array $fillable = ['column1', 'column2', ...];
 *
 * Usage:
 *   // Find by primary key
 *   $user = User::find(1);
 *
 *   // Find by any column
 *   $user = User::findBy('email', 'test@example.com');
 *
 *   // Get all records
 *   $users = User::all();
 *
 *   // Query with conditions
 *   $active = User::where(['is_active' => 1]);
 *
 *   // Create a new record
 *   $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
 *
 *   // Update a record
 *   $user->update(['name' => 'Alice Smith']);
 *
 *   // Delete a record
 *   $user->delete();
 *
 *   // Access properties
 *   echo $user->name;
 *   echo $user->email;
 */
abstract class Model
{
    /**
     * The database table this model reads from and writes to.
     * Each child class MUST define this.
     */
    protected static string $table = '';

    /**
     * The columns that are allowed to be mass-assigned via create() and save().
     * Any column not in this list is ignored (protects against mass assignment attacks).
     */
    protected array $fillable = [];

    /**
     * The raw data from the database row.
     * We use this internally to track the model's state.
     */
    protected array $attributes = [];

    // ─── Magic property access ────────────────────────────────────────────

    /**
     * Allow reading model attributes like object properties:
     *   $user->name    instead of    $user->attributes['name']
     */
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Allow setting model attributes like object properties:
     *   $user->name = 'Alice'    instead of    $user->attributes['name'] = 'Alice'
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    // ─── Database connection ──────────────────────────────────────────────

    /**
     * Get the PDO connection.
     */
    protected static function db(): \PDO
    {
        return Database::getInstance()->getConnection();
    }

    // ─── Query methods ────────────────────────────────────────────────────

    /**
     * Find a record by its primary key (id).
     * Returns null if not found.
     */
    public static function find(int $id): ?static
    {
        $table = static::$table;
        $stmt  = static::db()->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? static::hydrate($row) : null;
    }

    /**
     * Find a single record where a specific column equals a value.
     * Returns null if not found.
     *
     * Example: User::findBy('email', 'alice@example.com')
     */
    public static function findBy(string $column, mixed $value): ?static
    {
        $table = static::$table;
        // Note: column name is NOT user input so we can safely embed it.
        // Values are always bound as parameters.
        $stmt  = static::db()->prepare("SELECT * FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
        $stmt->execute([$value]);
        $row = $stmt->fetch();

        return $row ? static::hydrate($row) : null;
    }

    /**
     * Get all records from the table, ordered by a column.
     *
     * Example: User::all('created_at', 'DESC')
     */
    public static function all(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $table = static::$table;
        $dir   = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC'; // whitelist direction
        $stmt  = static::db()->query("SELECT * FROM `{$table}` ORDER BY `{$orderBy}` {$dir}");

        return array_map(fn($row) => static::hydrate($row), $stmt->fetchAll());
    }

    /**
     * Get records matching a set of conditions (AND logic).
     *
     * Example:
     *   Recipient::where(['is_suppressed' => 0], 'created_at', 'DESC', 20)
     *
     * This generates: WHERE is_suppressed = ? ORDER BY created_at DESC LIMIT 20
     */
    public static function where(
        array   $conditions,
        string  $orderBy = 'id',
        string  $dir     = 'ASC',
        ?int    $limit   = null,
        int     $offset  = 0
    ): array {
        $table  = static::$table;
        $dir    = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $wheres = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $wheres[] = "`{$column}` = ?";
            $values[] = $value;
        }

        $whereClause = !empty($wheres) ? 'WHERE ' . implode(' AND ', $wheres) : '';
        $limitClause = $limit !== null ? "LIMIT {$limit} OFFSET {$offset}" : '';

        $sql  = "SELECT * FROM `{$table}` {$whereClause} ORDER BY `{$orderBy}` {$dir} {$limitClause}";
        $stmt = static::db()->prepare($sql);
        $stmt->execute($values);

        return array_map(fn($row) => static::hydrate($row), $stmt->fetchAll());
    }

    /**
     * Count records in the table, optionally filtered by conditions.
     *
     * Example: User::count()  or  Recipient::count(['is_suppressed' => 1])
     */
    public static function count(array $conditions = []): int
    {
        $table  = static::$table;
        $wheres = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $wheres[] = "`{$column}` = ?";
            $values[] = $value;
        }

        $whereClause = !empty($wheres) ? 'WHERE ' . implode(' AND ', $wheres) : '';
        $stmt = static::db()->prepare("SELECT COUNT(*) FROM `{$table}` {$whereClause}");
        $stmt->execute($values);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Get paginated records.
     *
     * Returns an array with:
     *   'data'      => array of model instances for the current page
     *   'total'     => total number of records across all pages
     *   'page'      => current page number
     *   'per_page'  => records per page
     *   'last_page' => total number of pages
     *
     * Example: Recipient::paginate(20, 1)
     */
    public static function paginate(int $perPage, int $page, array $conditions = []): array
    {
        $page   = max(1, $page); // Page must be at least 1
        $offset = ($page - 1) * $perPage;
        $total  = static::count($conditions);

        $data = static::where($conditions, 'id', 'DESC', $perPage, $offset);

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    // ─── Write methods ────────────────────────────────────────────────────

    /**
     * Create a new record and return the populated model instance.
     *
     * Only keys listed in $fillable are inserted.
     *
     * Example: User::create(['name' => 'Alice', 'email' => 'alice@test.com'])
     */
    public static function create(array $data): static
    {
        $instance = new static();
        $instance->fill($data);
        $instance->save();
        return $instance;
    }

    /**
     * Save the current model to the database.
     * If the model has an 'id', it runs UPDATE. Otherwise it runs INSERT.
     */
    public function save(): bool
    {
        $table = static::$table;
        $data  = $this->getFillableData();

        if (empty($data)) {
            return false;
        }

        if (isset($this->attributes['id'])) {
            // UPDATE existing record
            $sets   = array_map(fn($col) => "`{$col}` = ?", array_keys($data));
            $values = array_values($data);
            $values[] = $this->attributes['id']; // for the WHERE clause

            $sql  = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = static::db()->prepare($sql);
            return $stmt->execute($values);

        } else {
            // INSERT new record
            $columns      = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = static::db()->prepare($sql);
            $result = $stmt->execute(array_values($data));

            if ($result) {
                // Store the new auto-increment ID back on the model
                $this->attributes['id'] = (int)static::db()->lastInsertId();
            }

            return $result;
        }
    }

    /**
     * Update specific columns on the current model and save.
     *
     * Example: $user->update(['name' => 'Bob'])
     */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }

    /**
     * Delete this record from the database.
     */
    public function delete(): bool
    {
        $table = static::$table;

        if (!isset($this->attributes['id'])) {
            return false;
        }

        $stmt = static::db()->prepare("DELETE FROM `{$table}` WHERE id = ?");
        return $stmt->execute([$this->attributes['id']]);
    }

    // ─── Raw SQL ──────────────────────────────────────────────────────────

    /**
     * Run a custom SQL query and return all results as model instances.
     * Use this for complex queries that don't fit the standard methods.
     *
     * Example:
     *   Recipient::raw(
     *       'SELECT * FROM recipients WHERE email LIKE ? AND is_suppressed = 0',
     *       ['%alice%']
     *   )
     */
    public static function raw(string $sql, array $bindings = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($bindings);
        return array_map(fn($row) => static::hydrate($row), $stmt->fetchAll());
    }

    /**
     * Run a custom SQL query and return a single result as a model instance.
     * Returns null if no row matches.
     */
    public static function rawOne(string $sql, array $bindings = []): ?static
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch();

        return $row ? static::hydrate($row) : null;
    }

    // ─── Utilities ────────────────────────────────────────────────────────

    /**
     * Convert this model's attributes to a plain PHP array.
     * Useful for passing data to views or API responses.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    /**
     * Create a model instance from a database row (associative array).
     */
    protected static function hydrate(array $row): static
    {
        $instance = new static();
        $instance->attributes = $row;
        return $instance;
    }

    /**
     * Set multiple attributes at once, filtering to only $fillable columns.
     */
    protected function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            // Only allow columns that are listed in $fillable
            if (in_array($key, $this->fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    /**
     * Return only the attributes that are in the $fillable list.
     * Used internally before INSERT/UPDATE.
     */
    private function getFillableData(): array
    {
        $result = [];
        foreach ($this->fillable as $column) {
            if (array_key_exists($column, $this->attributes)) {
                $result[$column] = $this->attributes[$column];
            }
        }
        return $result;
    }
}
