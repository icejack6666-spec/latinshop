<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) {
    die('Acceso directo no permitido.');
}

final class CacheService
{
    private static ?CacheService $instance = null;

    private ?\Redis $redis;
    private bool    $enabled;

    public const TTL_SHORT  = 60;
    public const TTL_MEDIUM = 300;
    public const TTL_LONG   = 3600;

    private function __construct()
    {
        $conn          = RedisConnection::getInstance();
        $this->redis   = $conn->getRedis();
        $this->enabled = $conn->isAvailable();
    }

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function get(string $key): mixed
    {
        if (!$this->enabled || $this->redis === null) {
            return null;
        }

        try {
            $value = $this->redis->get($key);
            return ($value === false) ? null : $value;
        } catch (\Throwable $e) {
            $this->handleError($e, 'get', $key);
            return null;
        }
    }

    public function set(string $key, mixed $value, int $ttl = self::TTL_MEDIUM): bool
    {
        if (!$this->enabled || $this->redis === null) {
            return false;
        }

        try {
            if ($ttl > 0) {
                return (bool) $this->redis->setex($key, $ttl, $value);
            }
            return (bool) $this->redis->set($key, $value);
        } catch (\Throwable $e) {
            $this->handleError($e, 'set', $key);
            return false;
        }
    }

    public function delete(string ...$keys): bool
    {
        if (!$this->enabled || $this->redis === null || empty($keys)) {
            return false;
        }

        try {
            $this->redis->del($keys);
            return true;
        } catch (\Throwable $e) {
            $this->handleError($e, 'delete', implode(', ', $keys));
            return false;
        }
    }

    /**
     * Elimina todas las claves que coinciden con un patrón usando SCAN.
     * Guarda/restaura el prefix de forma segura aunque Redis falle.
     */
    public function invalidatePattern(string $pattern): int
    {
        if (!$this->enabled || $this->redis === null) {
            return 0;
        }

        $deleted     = 0;
        $iterator    = null;
        $fullPattern = REDIS_PREFIX . $pattern;

        try {
            // Quitar prefix para que SCAN trabaje con la clave completa
            $this->redis->setOption(\Redis::OPT_PREFIX, '');

            do {
                $keys = $this->redis->scan($iterator, $fullPattern, 100);
                if ($keys === false) {
                    break;
                }
                if (!empty($keys)) {
                    $this->redis->del($keys);
                    $deleted += count($keys);
                }
            } while ($iterator !== 0);

        } catch (\Throwable $e) {
            $this->handleError($e, 'invalidatePattern', $pattern);
        } finally {
            // Restaurar el prefix siempre, pero solo si redis sigue disponible
            if ($this->redis !== null) {
                try {
                    $this->redis->setOption(\Redis::OPT_PREFIX, REDIS_PREFIX);
                } catch (\Throwable) {
                    // Si falla aquí ya está marcado como no disponible
                }
            }
        }

        return $deleted;
    }

    public function has(string $key): bool
    {
        if (!$this->enabled || $this->redis === null) {
            return false;
        }

        try {
            return (bool) $this->redis->exists($key);
        } catch (\Throwable $e) {
            $this->handleError($e, 'has', $key);
            return false;
        }
    }

    public function remember(string $key, callable $callback, int $ttl = self::TTL_MEDIUM): mixed
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();

        if ($value !== null) {
            $this->set($key, $value, $ttl);
        }

        return $value;
    }

    public function increment(string $key, int $by = 1): int|false
    {
        if (!$this->enabled || $this->redis === null) {
            return false;
        }

        try {
            return $this->redis->incrBy($key, $by);
        } catch (\Throwable $e) {
            $this->handleError($e, 'increment', $key);
            return false;
        }
    }

    public function decrement(string $key, int $by = 1): int|false
    {
        if (!$this->enabled || $this->redis === null) {
            return false;
        }

        try {
            $val = $this->redis->decrBy($key, $by);
            if ($val < 0) {
                $this->redis->set($key, 0);
                return 0;
            }
            return $val;
        } catch (\Throwable $e) {
            $this->handleError($e, 'decrement', $key);
            return false;
        }
    }

    public function flush(): bool
    {
        if (!$this->enabled || $this->redis === null) {
            return false;
        }

        try {
            return (bool) $this->redis->flushDB();
        } catch (\Throwable $e) {
            $this->handleError($e, 'flush', '*');
            return false;
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    // ─── HELPERS DE DOMINIO ───────────────────────────────────────────────────

    public static function keyUserTickets(int $userId, int $page, string $status = ''): string
    {
        return sprintf('tickets:user:%d:%d:%s', $userId, $page, $status);
    }

    public static function keyTicket(int $ticketId): string
    {
        return 'ticket:' . $ticketId;
    }

    public static function keyOpenCount(int $userId): string
    {
        return 'open_count:user:' . $userId;
    }

    public static function keyAdminTicketStats(): string
    {
        return 'admin:stats:tickets';
    }

    public static function keyAdminDashboard(): string
    {
        return 'admin:stats:dashboard';
    }

    public static function keyUser(int $userId): string
    {
        return 'user:' . $userId;
    }

    // ─── INVALIDACIONES DE DOMINIO ────────────────────────────────────────────

    public function invalidateTicket(int $ticketId, int $userId): void
    {
        $this->delete(
            self::keyTicket($ticketId),
            self::keyOpenCount($userId),
            self::keyAdminTicketStats(),
        );

        $this->invalidatePattern('tickets:user:' . $userId . ':*');
    }

    public function invalidateAdminDashboard(): void
    {
        $this->delete(
            self::keyAdminDashboard(),
            self::keyAdminTicketStats(),
        );
    }

    public function invalidateUser(int $userId): void
    {
        $this->delete(self::keyUser($userId));
    }

    // ─── INTERNOS ─────────────────────────────────────────────────────────────

    private function handleError(\Throwable $e, string $op, string $key): void
    {
        error_log(sprintf(
            '[CacheService] Error en %s("%s"): %s',
            $op,
            $key,
            $e->getMessage()
        ));

        $this->enabled = false;
        $this->redis   = null; // evitar más llamadas a un objeto en estado inválido
    }

    private function __clone() {}

    /** @throws \Exception */
    public function __wakeup(): void
    {
        throw new \Exception('No se puede deserializar CacheService.');
    }
}