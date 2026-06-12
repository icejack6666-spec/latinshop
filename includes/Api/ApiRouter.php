<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * ApiRouter
 *
 * Front-controller minimalista para la API REST v1.
 * Gestiona rutas, CORS, logging y despacho a endpoints.
 *
 * Convención de rutas:
 *   GET    /api/v1/tickets          → TicketsEndpoint::index()
 *   POST   /api/v1/tickets          → TicketsEndpoint::store()
 *   GET    /api/v1/tickets/{id}     → TicketsEndpoint::show()
 *   POST   /api/v1/tickets/{id}/reply → TicketsEndpoint::reply()
 *   PATCH  /api/v1/tickets/{id}/status → TicketsEndpoint::changeStatus()
 *   GET    /api/v1/profile          → ProfileEndpoint::show()
 *   GET    /api/v1/profile/keys     → ProfileEndpoint::keys()
 *   POST   /api/v1/profile/keys     → ProfileEndpoint::createKey()
 *   DELETE /api/v1/profile/keys/{id} → ProfileEndpoint::revokeKey()
 *   GET    /api/v1/admin/tickets    → AdminTicketsEndpoint::index()
 *   PATCH  /api/v1/admin/tickets/{id} → AdminTicketsEndpoint::update()
 */
class ApiRouter
{
    private ApiAuth      $auth;
    private ApiRateLimit $rateLimit;
    private float        $startTime;

    /** Rutas: [method, regex, class, method_name, param_names[]] */
    private array $routes = [];

    public function __construct(ApiAuth $auth, ApiRateLimit $rateLimit)
    {
        $this->auth      = $auth;
        $this->rateLimit = $rateLimit;
        $this->startTime = microtime(true);
        $this->registerRoutes();
    }


    private function registerRoutes(): void
    {
        $this->add('GET',    '/tickets',                       'TicketsEndpoint', 'index');
        $this->add('POST',   '/tickets',                       'TicketsEndpoint', 'store');
        $this->add('GET',    '/tickets/(\d+)',                 'TicketsEndpoint', 'show',         ['id']);
        $this->add('POST',   '/tickets/(\d+)/reply',           'TicketsEndpoint', 'reply',        ['id']);
        $this->add('PATCH',  '/tickets/(\d+)/status',          'TicketsEndpoint', 'changeStatus', ['id']);
        $this->add('PATCH',  '/tickets/(\d+)/close',           'TicketsEndpoint', 'close',        ['id']);

        $this->add('GET',    '/profile',                       'ProfileEndpoint', 'show');
        $this->add('GET',    '/profile/keys',                  'ProfileEndpoint', 'keys');
        $this->add('POST',   '/profile/keys',                  'ProfileEndpoint', 'createKey');
        $this->add('DELETE', '/profile/keys/(\d+)',            'ProfileEndpoint', 'revokeKey',    ['key_id']);

        $this->add('GET',    '/admin/tickets',                 'AdminTicketsEndpoint', 'index');
        $this->add('GET',    '/admin/tickets/(\d+)',           'AdminTicketsEndpoint', 'show',         ['id']);
        $this->add('PATCH',  '/admin/tickets/(\d+)',           'AdminTicketsEndpoint', 'update',        ['id']);
        $this->add('POST',   '/admin/tickets/(\d+)/reply',     'AdminTicketsEndpoint', 'reply',         ['id']);
        $this->add('GET',    '/admin/stats',                   'AdminTicketsEndpoint', 'stats');
    }

    private function add(
        string $method,
        string $path,
        string $class,
        string $action,
        array  $params = []
    ): void {
        $this->routes[] = [$method, '#^' . $path . '$#', $class, $action, $params];
    }


    public function dispatch(): void
    {
        $this->setCorsHeaders();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $method   = $_SERVER['REQUEST_METHOD'];
        $uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = '/api/v1';

        $scriptDir = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
        if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        if (!str_starts_with($uri, $basePath)) {
            ApiResponse::notFound('Endpoint no encontrado.');
        }
        $path = substr($uri, strlen($basePath));
        $path = '/' . ltrim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $authenticated = $this->auth->authenticate();
        $user   = $this->auth->getUser();
        $role   = $authenticated ? ($user['role'] ?? 'anon') : 'anon';
        $ident  = $authenticated ? (string)($this->auth->getKeyId() ?? $user['id']) : null;

        $this->rateLimit->check($path, $ident, $role);

        foreach ($this->routes as [$routeMethod, $regex, $class, $action, $paramNames]) {
            if ($method !== $routeMethod) continue;
            if (!preg_match($regex, $path, $matches)) continue;

            $params = [];
            array_shift($matches); 
            foreach ($paramNames as $i => $name) {
                $params[$name] = $matches[$i] ?? null;
            }

            $this->runEndpoint($class, $action, $params);
            return;
        }

        $allowedMethods = [];
        foreach ($this->routes as [$routeMethod, $regex]) {
            if (preg_match($regex, $path)) {
                $allowedMethods[] = $routeMethod;
            }
        }

        if (!empty($allowedMethods)) {
            ApiResponse::methodNotAllowed(array_unique($allowedMethods));
        }

        ApiResponse::notFound("Endpoint '{$path}' no existe.");
    }


    private function runEndpoint(string $class, string $action, array $params): void
    {
        $file = API_ENDPOINTS_PATH . '/' . $class . '.php';

        if (!file_exists($file)) {
            error_log("[API] Endpoint file missing: $file");
            ApiResponse::internalError();
        }

        require_once $file;

        if (!class_exists($class)) {
            error_log("[API] Endpoint class missing: $class");
            ApiResponse::internalError();
        }

        $endpoint = new $class($this->auth);

        if (!method_exists($endpoint, $action)) {
            error_log("[API] Endpoint method missing: {$class}::{$action}");
            ApiResponse::internalError();
        }

        try {
            $endpoint->$action($params);
        } catch (\Throwable $e) {
            error_log("[API] Uncaught exception in {$class}::{$action}: " . $e->getMessage());
            if (ENV === 'development') {
                ApiResponse::error(ApiResponse::ERR_INTERNAL, $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 500);
            }
            ApiResponse::internalError();
        } finally {
            $this->logRequest((int)http_response_code());
        }
    }

    private function setCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        $allowed = ENV === 'production'
            ? [SITE_URL]
            : ['*'];

        if (in_array('*', $allowed, true) || in_array($origin, $allowed, true)) {
            $originHeader = in_array('*', $allowed, true) ? '*' : $origin;
            header('Access-Control-Allow-Origin: '     . $originHeader);
            header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
            header('Access-Control-Max-Age: 86400');
        }

        header('Vary: Origin');
    }

    private function logRequest(int $statusCode): void
    {
        try {
            $elapsed = (int)round((microtime(true) - $this->startTime) * 1000);
            $user    = $this->auth->getUser();

            Database::getInstance()->insert(
                "INSERT INTO api_request_log
                    (api_key_id, user_id, method, endpoint, status_code, response_ms, ip_address, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $this->auth->getKeyId(),
                    $user['id'] ?? null,
                    $_SERVER['REQUEST_METHOD'],
                    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
                    $statusCode,
                    min($elapsed, 65535),
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]
            );
        } catch (\Exception) { /* non-fatal */ }
    }
}
