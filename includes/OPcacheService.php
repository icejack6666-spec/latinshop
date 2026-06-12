<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * ============================================================
 * OPcacheService
 * ============================================================
 * Gestiona y monitorea OPcache en el contexto de LatinShop.
 *
 * Funcionalidades:
 *   - isAvailable()     → detecta si OPcache está activo
 *   - getStatus()       → estadísticas completas
 *   - getHitRatio()     → % de aciertos del caché
 *   - invalidate()      → invalida un archivo específico
 *   - flush()           → vacía todo el caché (deploy hook)
 *   - warmup()          → precarga archivos PHP del proyecto
 *   - getSummary()      → resumen para el panel admin
 *
 * SEGURIDAD: Este servicio solo expone información sensible
 * a través del panel admin (requireRole('admin')).
 * La API pública de OPcache puede filtrar rutas del servidor,
 * por eso NUNCA se llama desde páginas públicas.
 */
class OPcacheService
{
    private const WARMUP_FILES = [
        'config/config.php',
        'includes/Database.php',
        'includes/Auth.php',
        'includes/security.php',
        'includes/seguridad_vistas.php',
        'includes/SecurityClass.php',
        'includes/AuditLog.php',
        'includes/Notifications.php',
        'includes/Mailer.php',
        'includes/TOTP.php',
        'includes/Support/SupportTicketRepository.php',
        'includes/Support/SupportMessageRepository.php',
        'includes/Support/AttachmentRepository.php',
        'includes/Support/AttachmentService.php',
        'includes/Support/SupportTicketService.php',
        'includes/Backup/BackupService.php',
        'index.php',
    ];


    /**
     * Verifica si OPcache está disponible y activo.
     */
    public function isAvailable(): bool
    {
        return function_exists('opcache_get_status')
            && (bool) ini_get('opcache.enable');
    }

    /**
     * Verifica disponibilidad para PHP CLI
     * (útil para scripts de cron/deploy).
     */
    public function isAvailableCli(): bool
    {
        return function_exists('opcache_get_status')
            && (bool) ini_get('opcache.enable_cli');
    }

    // ── Estadísticas ───────────────────────────────────────────

    /**
     * Devuelve el estado completo de OPcache.
     *
     * @return array|null  null si OPcache no está disponible
     */
    public function getStatus(): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $raw = opcache_get_status(false);

        if ($raw === false) {
            return null;
        }

        return $raw;
    }

    /**
     * Devuelve el porcentaje de aciertos del caché (hit ratio).
     * Un valor > 90% es saludable en producción.
     *
     * @return float  0.0 - 100.0, o -1.0 si no disponible
     */
    public function getHitRatio(): float
    {
        $status = $this->getStatus();

        if ($status === null) {
            return -1.0;
        }

        $hits   = (int) ($status['opcache_statistics']['hits']         ?? 0);
        $misses = (int) ($status['opcache_statistics']['misses']       ?? 0);
        $total  = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Resumen compacto para el panel admin.
     *
     * @return array{
     *   available: bool,
     *   enabled: bool,
     *   hit_ratio: float,
     *   memory_used_mb: float,
     *   memory_free_mb: float,
     *   memory_total_mb: float,
     *   memory_pct: float,
     *   cached_scripts: int,
     *   hits: int,
     *   misses: int,
     *   restarts: int,
     *   jit_enabled: bool,
     *   jit_buffer_mb: float,
     *   jit_buffer_free_mb: float,
     *   validate_timestamps: bool,
     *   uptime_seconds: int,
     * }
     */
    public function getSummary(): array
    {
        $base = [
            'available'          => $this->isAvailable(),
            'enabled'            => (bool) ini_get('opcache.enable'),
            'hit_ratio'          => -1.0,
            'memory_used_mb'     => 0.0,
            'memory_free_mb'     => 0.0,
            'memory_total_mb'    => 0.0,
            'memory_pct'         => 0.0,
            'cached_scripts'     => 0,
            'hits'               => 0,
            'misses'             => 0,
            'restarts'           => 0,
            'jit_enabled'        => false,
            'jit_buffer_mb'      => 0.0,
            'jit_buffer_free_mb' => 0.0,
            'validate_timestamps'=> (bool) ini_get('opcache.validate_timestamps'),
            'uptime_seconds'     => 0,
        ];

        $status = $this->getStatus();

        if ($status === null) {
            return $base;
        }

        $mem   = $status['memory_usage']      ?? [];
        $stats = $status['opcache_statistics'] ?? [];
        $jit   = $status['jit']               ?? [];

        $usedBytes  = (int) ($mem['used_memory']      ?? 0);
        $freeBytes  = (int) ($mem['free_memory']      ?? 0);
        $wastedBytes= (int) ($mem['wasted_memory']    ?? 0);
        $totalBytes = $usedBytes + $freeBytes + $wastedBytes;

        $base['hit_ratio']          = $this->getHitRatio();
        $base['memory_used_mb']     = round($usedBytes  / 1_048_576, 2);
        $base['memory_free_mb']     = round($freeBytes  / 1_048_576, 2);
        $base['memory_total_mb']    = round($totalBytes / 1_048_576, 2);
        $base['memory_pct']         = $totalBytes > 0
            ? round(($usedBytes / $totalBytes) * 100, 1)
            : 0.0;
        $base['cached_scripts']     = (int) ($stats['num_cached_scripts']  ?? 0);
        $base['hits']               = (int) ($stats['hits']                ?? 0);
        $base['misses']             = (int) ($stats['misses']              ?? 0);
        $base['restarts']           = (int) ($stats['oom_restarts']        ?? 0)
                                    + (int) ($stats['hash_restarts']       ?? 0)
                                    + (int) ($stats['manual_restarts']     ?? 0);
        $base['uptime_seconds']     = (int) ($stats['opcache_hit_rate']    ?? 0);
        $base['uptime_seconds']     = (int) ($stats['start_time']          ?? 0) > 0
            ? time() - (int) $stats['start_time']
            : 0;

        // JIT (PHP 8.x)
        if (!empty($jit)) {
            $base['jit_enabled']        = (bool) ($jit['enabled']     ?? false);
            $jitBuffer = (int) ($jit['buffer_size'] ?? 0);
            $jitFree   = (int) ($jit['buffer_free'] ?? 0);
            $base['jit_buffer_mb']      = round($jitBuffer / 1_048_576, 2);
            $base['jit_buffer_free_mb'] = round($jitFree   / 1_048_576, 2);
        }

        return $base;
    }


    /**
     * Invalida un archivo específico del caché.
     * Útil cuando se actualiza un solo archivo en producción.
     *
     * @param  string $relativePath  Ruta relativa a ROOT_PATH
     * @param  bool   $force         true = invalida aunque no haya cambiado
     * @return bool
     */
    public function invalidate(string $relativePath, bool $force = false): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $absolutePath = ROOT_PATH . '/' . ltrim($relativePath, '/');

        if (!file_exists($absolutePath)) {
            return false;
        }

        return opcache_invalidate($absolutePath, $force);
    }

    /**
     * Vacía COMPLETAMENTE el caché OPcache.
     * Llamar después de un deploy para evitar servir código viejo.
     *
     * @return bool
     */
    public function flush(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $result = opcache_reset();

        error_log('[OPcacheService] Cache flushed. Result: ' . ($result ? 'OK' : 'FAIL'));

        return $result;
    }

    /**
     * Precarga (warmup) los archivos críticos del proyecto.
     * Los compila y mete en caché para que la primera request
     * real no sufra el costo de compilación.
     *
     * @return array{compiled: int, failed: int, skipped: int}
     */
    public function warmup(): array
    {
        $compiled = 0;
        $failed   = 0;
        $skipped  = 0;

        if (!$this->isAvailable()) {
            return ['compiled' => 0, 'failed' => 0, 'skipped' => (int) count(self::WARMUP_FILES)];
        }

        foreach (self::WARMUP_FILES as $relPath) {
            $abs = ROOT_PATH . '/' . $relPath;

            if (!file_exists($abs)) {
                $skipped++;
                continue;
            }

            if (opcache_compile_file($abs)) {
                $compiled++;
            } else {
                $failed++;
                error_log("[OPcacheService] No se pudo compilar: {$abs}");
            }
        }

        return ['compiled' => $compiled, 'failed' => $failed, 'skipped' => $skipped];
    }

    /**
     * Devuelve la configuración activa de OPcache.
     * Solo las claves relevantes (sin paths del servidor).
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        if (!function_exists('opcache_get_configuration')) {
            return [];
        }

        $raw = opcache_get_configuration();

        // Devolver solo las directivas, no las rutas internas
        return $raw['directives'] ?? [];
    }


    /**
     * Verifica si validate_timestamps está activo.
     * En producción debe estar OFF (=0) para máximo rendimiento.
     * En development debe estar ON (=1) para ver cambios.
     */
    public function isValidateTimestampsEnabled(): bool
    {
        return (bool) ini_get('opcache.validate_timestamps');
    }

    /**
     * Devuelve una advertencia de configuración si algo está mal.
     *
     * @return string[] Lista de advertencias
     */
    public function getConfigWarnings(): array
    {
        $warnings = [];

        if (!$this->isAvailable()) {
            $warnings[] = 'OPcache no está disponible o no está activado.';
            return $warnings;
        }

        $summary = $this->getSummary();

        if (ENV === 'production' && $summary['validate_timestamps']) {
            $warnings[] = 'opcache.validate_timestamps está activo en producción. Desactívalo para mejor rendimiento.';
        }

        if ($summary['memory_pct'] > 85) {
            $warnings[] = "Uso de memoria OPcache alto ({$summary['memory_pct']}%). Considera aumentar opcache.memory_consumption.";
        }

        if ($summary['hit_ratio'] >= 0 && $summary['hit_ratio'] < 80) {
            $warnings[] = "Hit ratio bajo ({$summary['hit_ratio']}%). OPcache puede no estar funcionando correctamente.";
        }

        if ($summary['restarts'] > 0) {
            $warnings[] = "Se han producido {$summary['restarts']} reinicios de OPcache. Puede haber memoria insuficiente.";
        }

        return $warnings;
    }
}
