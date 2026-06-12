<?php
declare(strict_types=1);

class DatabaseStub
{
    private static ?DatabaseStub $instance = null;
    private PDO $pdo;
    private static string $schema = '';

    private function __construct()
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        if (self::$schema !== '') {
            $this->pdo->exec(self::$schema);
        }
    }

    public static function getInstance(): DatabaseStub
    {
        if (self::$instance === null) {
            self::$instance = new DatabaseStub();
        }
        return self::$instance;
    }

    public static function reset(): void { self::$instance = null; }

    public static function loadSchema(string $sql): void
    {
        self::$schema   = $sql;
        self::$instance = null;
    }

    /** Traducir funciones MySQL → SQLite */
    private function normalize(string $sql): string
    {
        // NOW() → datetime('now')
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);
        // DATE_SUB(x, INTERVAL n SECOND) → datetime(x, '-n seconds')
        $sql = preg_replace_callback(
            "/DATE_SUB\((\w+),\s*INTERVAL\s+(\?|\d+)\s+SECOND\)/i",
            fn($m) => "datetime({$m[1]}, '-' || {$m[2]} || ' seconds')",
            $sql
        );
        // FIELD(col,'a','b','c') → CASE col WHEN 'a' THEN 1 WHEN 'b' THEN 2 ... END
        $sql = preg_replace_callback(
            "/FIELD\(([^,]+),\s*([^)]+)\)/i",
            function ($m) {
                $col    = trim($m[1]);
                $vals   = array_map('trim', explode(',', $m[2]));
                $cases  = '';
                foreach ($vals as $i => $v) {
                    $cases .= " WHEN {$v} THEN " . ($i + 1);
                }
                return "CASE {$col}{$cases} ELSE 999 END";
            },
            $sql
        );
        return $sql;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($this->normalize($sql));
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function count(string $sql, array $params = []): int
    {
        return (int)$this->query($sql, $params)->fetchColumn();
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void           { $this->pdo->commit(); }
    public function rollback(): void         { $this->pdo->rollBack(); }
    public function getPdo(): PDO            { return $this->pdo; }

    private function __clone() {}
    public function __wakeup(): void { throw new \Exception('No serializable.'); }
}
