<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * ApiResponse
 *
 * Centraliza el envío de respuestas JSON con estructura uniforme:
 *
 * Éxito:
 * {
 *   "success": true,
 *   "data": { ... },
 *   "meta": { "page": 1, "total": 50, ... }   // opcional
 * }
 *
 * Error:
 * {
 *   "success": false,
 *   "error": {
 *     "code": "NOT_FOUND",
 *     "message": "Ticket no encontrado."
 *   }
 * }
 */
final class ApiResponse
{

    public const ERR_UNAUTHORIZED      = 'UNAUTHORIZED';
    public const ERR_FORBIDDEN         = 'FORBIDDEN';
    public const ERR_NOT_FOUND         = 'NOT_FOUND';
    public const ERR_VALIDATION        = 'VALIDATION_ERROR';
    public const ERR_RATE_LIMIT        = 'RATE_LIMIT_EXCEEDED';
    public const ERR_METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    public const ERR_INTERNAL          = 'INTERNAL_ERROR';
    public const ERR_INVALID_SCOPE     = 'INSUFFICIENT_SCOPE';


    /**
     * Envía una respuesta 2xx con datos.
     *
     * @param mixed      $data
     * @param array|null $meta   Metadatos de paginación u otros
     * @param int        $status HTTP status code
     */
    public static function ok(mixed $data, ?array $meta = null, int $status = 200): never
    {
        $body = ['success' => true, 'data' => $data];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }
        self::send($body, $status);
    }

    /**
     * Respuesta 201 Created con Location header.
     */
    public static function created(mixed $data, string $location = ''): never
    {
        if ($location !== '') {
            header('Location: ' . $location);
        }
        self::ok($data, null, 201);
    }

    /**
     * Respuesta 204 No Content (sin cuerpo).
     */
    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }


    public static function unauthorized(string $message = 'Autenticación requerida.'): never
    {
        self::error(self::ERR_UNAUTHORIZED, $message, 401);
    }

    public static function forbidden(string $message = 'Acceso denegado.'): never
    {
        self::error(self::ERR_FORBIDDEN, $message, 403);
    }

    public static function notFound(string $message = 'Recurso no encontrado.'): never
    {
        self::error(self::ERR_NOT_FOUND, $message, 404);
    }

    public static function methodNotAllowed(array $allowed = []): never
    {
        if (!empty($allowed)) {
            header('Allow: ' . implode(', ', $allowed));
        }
        self::error(self::ERR_METHOD_NOT_ALLOWED, 'Método HTTP no permitido.', 405);
    }

    public static function validationError(string|array $messages): never
    {
        $msgs = is_array($messages) ? $messages : [$messages];
        self::send([
            'success' => false,
            'error'   => [
                'code'     => self::ERR_VALIDATION,
                'message'  => 'Los datos enviados no son válidos.',
                'details'  => $msgs,
            ],
        ], 422);
    }

    public static function rateLimitExceeded(int $retryAfter = 60): never
    {
        header('Retry-After: ' . $retryAfter);
        self::error(self::ERR_RATE_LIMIT, 'Límite de peticiones superado. Intenta en ' . $retryAfter . ' segundos.', 429);
    }

    public static function insufficientScope(string $required): never
    {
        self::error(self::ERR_INVALID_SCOPE, "Este endpoint requiere el scope: {$required}", 403);
    }

    public static function internalError(string $message = 'Error interno del servidor.'): never
    {
        self::error(self::ERR_INTERNAL, $message, 500);
    }


    public static function error(string $code, string $message, int $status): never
    {
        self::send([
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ], $status);
    }


    private static function send(array $body, int $status): never
    {
        // Limpiar cualquier output previo (por si algún include emitió algo)
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
