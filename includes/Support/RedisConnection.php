<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) {
    die('Acceso directo no permitido.');
}

/**
 * RedisConnection
 *
 * Singleton que gestiona la conexión a Redis usando la extensión phpredis.
 * Si Redis no está disponible, $available queda en false y CacheService
 * opera en modo no-op, sin lanzar excepciones al resto de la aplicación.
 *
 * Requiere: extensión phpredis (php8.3-redis).
 */
final class RedisConnection
{
    private static ?RedisConnection $instance = null;

    /** @var \Redis|null */
    private ?\Redis $redis = null;

    private bool $available = false;

    private function __construct()
    {
        if (!extension_loaded('redis')) {
            error_log('[RedisConnection] Extensión phpredis no disponible.');
            return;
        }

        try {
            $host    = REDIS_HOST;
            $port    = REDIS_PORT;
            $pass    = REDIS_PASS;
            $db      = REDIS_DB;
            $timeout = 1.5; // segundos — falla rápido si Redis está caído

            $this->redis = new \Redis();

            $connected = $this->redis->connect($host, $port, $timeout);

            if (!$connected) {
                throw new \RuntimeException("No se pudo conectar a Redis en {$host}:{$port}");
            }

            if ($pass !== '') {
                $this->redis->auth($pass);
            }

            $this->redis->select($db);
            $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            $this->redis->setOption(\Redis::OPT_PREFIX, REDIS_PREFIX);

            $this->available = true;

        } catch (\Throwable $e) {
            error_log('[RedisConnection] Error: ' . $e->getMessage());
            $this->redis     = null;
            $this->available = false;
        }
    }

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /** Devuelve la instancia Redis nativa o null si no está disponible. */
    public function getRedis(): ?\Redis
    {
        return $this->redis;
    }

    /** Indica si Redis está operativo. */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    private function __clone() {}

    /** @throws \Exception */
    public function __wakeup(): void
    {
        throw new \Exception('No se puede deserializar RedisConnection.');
    }
}
