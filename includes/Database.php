<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            if (ENV === 'development') {
                die('Error de conexión DB: ' . $e->getMessage());
            } else {
                error_log('[LatinShop] Error DB: ' . $e->getMessage());
                die('Error interno del servidor. Intenta más tarde.');
            }
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    private function __clone() {}
    public function __wakeup(): void
    {
        throw new \Exception('No se puede deserializar un singleton.');
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('[LatinShop] Query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            if (ENV === 'development') {
                throw $e;
            }
            throw new \RuntimeException('Error al ejecutar la consulta.');
        }
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
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function count(string $sql, array $params = []): int
    {
        $result = $this->query($sql, $params)->fetchColumn();
        return (int) $result;
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
