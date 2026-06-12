<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * Security  (orchestrator)
 *
 * Singleton that wires together the four focused helpers and exposes
 * the surface that the rest of the application actually uses:
 *
 *   Security::getInstance()->checkRequest()   — call once in index.php
 *   Security::getInstance()->blockIP(...)
 *   Security::getInstance()->unblockIP(...)
 *   Security::getInstance()->autoBlockOnFailedLogins()
 *   Security::getInstance()->logEvent(...)
 *   Security::getInstance()->getLogs(...)
 *   Security::getInstance()->getBlockedIPs()
 *   Security::getInstance()->getStats()
 *   Security::getInstance()->cleanOldLogs()
 *
 * Requires (loaded before this file):
 *   includes/Security/IpHelper.php
 *   includes/Security/BotDetector.php
 *   includes/Security/SecurityLogger.php
 *   includes/Security/RateLimiter.php
 *   includes/Security/InputScanner.php
 */
class Security
{
    private static ?Security $instance = null;

    private string          $ip;
    private SecurityLogger  $logger;
    private RateLimiter     $rateLimiter;
    private BotDetector     $botDetector;
    private InputScanner    $inputScanner;

    private function __construct()
    {
        $db = Database::getInstance();

        $this->ip           = IpHelper::getRealIP();
        $this->logger       = new SecurityLogger($db);
        $this->rateLimiter  = new RateLimiter($db, $this->logger);
        $this->botDetector  = new BotDetector();
        $this->inputScanner = new InputScanner();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // -------------------------------------------------------------------------
    // Main entry point — call once per request in index.php
    // -------------------------------------------------------------------------

    /**
     * Run all security checks for the current HTTP request.
     * Terminates with a 403 response if any check fails.
     */
    public function checkRequest(): void
    {
        $this->enforceIPBlock();
        $this->enforceBotBlock();
        $this->enforceInputScan();
    }

    // -------------------------------------------------------------------------
    // Delegating façade — keeps call-sites unchanged
    // -------------------------------------------------------------------------

    /** @see RateLimiter::blockIP() */
    public function blockIP(
        string $ip,
        string $reason    = '',
        bool   $permanent = false,
        int    $hours     = 24
    ): bool {
        return $this->rateLimiter->blockIP($ip, $reason, $permanent, $hours);
    }

    /** @see RateLimiter::unblockIP() */
    public function unblockIP(string $ip): bool
    {
        return $this->rateLimiter->unblockIP($ip);
    }

    /**
     * Auto-block the current request IP on excessive login failures.
     * @see RateLimiter::autoBlockOnFailedLogins()
     */
    public function autoBlockOnFailedLogins(
        int $maxAttempts   = 20,
        int $windowMinutes = 60,
        int $blockHours    = 24
    ): void {
        $this->rateLimiter->autoBlockOnFailedLogins(
            $this->ip,
            $maxAttempts,
            $windowMinutes,
            $blockHours
        );
    }

    /** @see SecurityLogger::log() */
    public function logEvent(
        string $eventType,
        string $description = '',
        ?int   $userId      = null
    ): void {
        $this->logger->log($eventType, $description, $this->ip, $userId);
    }

    /** @see SecurityLogger::getLogs() */
    public function getLogs(int $limit = 50, string $eventType = ''): array
    {
        return $this->logger->getLogs($limit, $eventType);
    }

    /** @see RateLimiter::getBlockedIPs() */
    public function getBlockedIPs(): array
    {
        return $this->rateLimiter->getBlockedIPs();
    }

    /** @see SecurityLogger::getStats() */
    public function getStats(): array
    {
        return $this->logger->getStats();
    }

    /** @see SecurityLogger::cleanOldLogs() */
    public function cleanOldLogs(int $days = 30): void
    {
        $this->logger->cleanOldLogs($days);
    }

    /** Expose the resolved IP so other components can read it without re-resolving. */
    public function getIP(): string
    {
        return $this->ip;
    }

    // -------------------------------------------------------------------------
    // Internal check steps
    // -------------------------------------------------------------------------

    private function enforceIPBlock(): void
    {
        $block = $this->rateLimiter->findBlock($this->ip);
        if ($block === null) {
            return;
        }

        $this->logger->log(
            'blocked_ip_attempt',
            'IP bloqueada intentó acceder: ' . $block['reason'],
            $this->ip
        );
        $this->denyAccess('Tu dirección IP ha sido bloqueada. Contacta al administrador si crees que es un error.');
    }

    private function enforceBotBlock(): void
    {
        $matched = $this->botDetector->detectCurrentRequest();
        if ($matched === null) {
            return;
        }

        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
        $this->logger->log('bad_bot', "Bot malicioso detectado: {$matched} | UA: {$ua}", $this->ip);
        $this->rateLimiter->blockIP($this->ip, "Bot malicioso: {$matched}", false, 72);
        $this->denyAccess('Acceso denegado.');
    }

    private function enforceInputScan(): void
    {
        // Admin routes are trusted; skip pattern scanning.
        if ($this->isAdminRoute()) {
            return;
        }

        $threat = $this->inputScanner->scanCurrentRequest();
        if ($threat === null) {
            return;
        }

        $preview = substr($threat['value'], 0, 200);

        if ($threat['type'] === 'xss') {
            $this->logger->log('xss_attempt', "Intento XSS detectado: {$preview}", $this->ip);
            $this->rateLimiter->blockIP($this->ip, 'Intento XSS detectado', false, 48);
        } else {
            $this->logger->log('sqli_attempt', "Intento SQLi detectado: {$preview}", $this->ip);
            $this->rateLimiter->blockIP($this->ip, 'Intento de inyección SQL', false, 72);
        }

        $this->denyAccess('Solicitud bloqueada por seguridad.');
    }

    /**
     * True when the current request targets an admin route AND the
     * session belongs to an admin user.
     */
    private function isAdminRoute(): bool
    {
        $uri       = $_SERVER['REQUEST_URI'] ?? '/';
        $path      = trim(strtok(parse_url($uri, PHP_URL_PATH) ?? '/', '?'), '/');
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

        if ($scriptDir) {
            $relative = ltrim($scriptDir, '/');
            if (str_starts_with($path, $relative)) {
                $path = trim(substr($path, strlen($relative)), '/');
            }
        }

        return str_starts_with($path, 'admin')
            && isset($_SESSION['user_role'])
            && $_SESSION['user_role'] === 'admin';
    }

    // -------------------------------------------------------------------------
    // 403 response
    // -------------------------------------------------------------------------

    private function denyAccess(string $message = 'Acceso denegado.'): never
    {
        http_response_code(403);

        $page403 = ROOT_PATH . '/pages/403.php';
        if (file_exists($page403)) {
            $_403_message = $message;
            require $page403;
        } else {
            // Inline fallback — intentionally minimal, no external dependencies.
            echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
            <title>403 Acceso Denegado | Latin Shop</title>
            <style>
                body{background:var(--tbt-bg-base);color:var(--tbt-txt-base);font-family:sans-serif;
                     display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
                .box{text-align:center;max-width:400px;padding:2rem;}
                h1{color:#f87171;font-size:3rem;margin:0 0 1rem;}
                p{color:var(--tbt-txt-sub);margin:0 0 1.5rem;}
                a{color:var(--tbt-jade);text-decoration:none;}
            </style></head>
            <body><div class='box'>
                <h1>403</h1>
                <p>{$message}</p>
                <a href='/'>← Volver al inicio</a>
            </div></body></html>";
        }

        exit;
    }
}
