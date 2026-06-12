<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * BaseEndpoint
 *
 * Clase base para todos los endpoints de la API v1.
 * Provee helpers para parsear JSON, validar entrada y acceder al contexto.
 */
abstract class BaseEndpoint
{
    protected ApiAuth $auth;

    public function __construct(ApiAuth $auth)
    {
        $this->auth = $auth;
    }


    /**
     * Parsea el body JSON del request.
     * Acepta también form-encoded (multipart o x-www-form-urlencoded).
     */
    protected function body(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // JSON body
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw === '' || $raw === false) return [];
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                ApiResponse::error(ApiResponse::ERR_VALIDATION, 'JSON malformado: ' . json_last_error_msg(), 400);
            }
            return is_array($data) ? $data : [];
        }

        return $_POST;
    }

    /**
     * Obtiene un campo del body con cast de tipo.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->body()[$key] ?? $default;
    }


    /**
     * Valida campos requeridos.
     * Emite 422 si faltan.
     *
     * @param array  $data    Array de datos a validar
     * @param array  $rules   ['campo' => 'string|int|min:N|max:N|in:a,b,c']
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleStr) {
            $parts = explode('|', $ruleStr);
            $value = $data[$field] ?? null;

            foreach ($parts as $rule) {
                [$ruleName, $ruleParam] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || $value === '') {
                            $errors[] = "El campo '{$field}' es obligatorio.";
                        }
                        break;

                    case 'string':
                        if ($value !== null && !is_string($value)) {
                            $errors[] = "El campo '{$field}' debe ser texto.";
                        }
                        break;

                    case 'int':
                    case 'integer':
                        if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $errors[] = "El campo '{$field}' debe ser un entero.";
                        }
                        break;

                    case 'min':
                        $min = (int)$ruleParam;
                        if (is_string($value) && mb_strlen($value) < $min) {
                            $errors[] = "El campo '{$field}' debe tener al menos {$min} caracteres.";
                        }
                        if (is_int($value) && $value < $min) {
                            $errors[] = "El campo '{$field}' debe ser al menos {$min}.";
                        }
                        break;

                    case 'max':
                        $max = (int)$ruleParam;
                        if (is_string($value) && mb_strlen($value) > $max) {
                            $errors[] = "El campo '{$field}' no puede superar {$max} caracteres.";
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', $ruleParam ?? '');
                        if ($value !== null && !in_array($value, $allowed, true)) {
                            $errors[] = "El campo '{$field}' debe ser uno de: " . implode(', ', $allowed) . '.';
                        }
                        break;

                    case 'bool':
                        if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', true, false], true)) {
                            $errors[] = "El campo '{$field}' debe ser booleano.";
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }

        return $data;
    }


    /**
     * Obtiene parámetros de query string seguros.
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Parsea y valida un parámetro de paginación.
     * Devuelve ['page' => int, 'per_page' => int, 'offset' => int].
     */
    protected function pagination(int $maxPerPage = 50): array
    {
        $page    = max(1, (int)($this->query('page', 1)));
        $perPage = min($maxPerPage, max(1, (int)($this->query('per_page', 20))));
        return [
            'page'     => $page,
            'per_page' => $perPage,
            'offset'   => ($page - 1) * $perPage,
        ];
    }

    /**
     * Construye los metadatos de paginación para ApiResponse::ok().
     */
    protected function paginationMeta(int $total, int $page, int $perPage): array
    {
        return [
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => (int)ceil($total / max(1, $perPage)),
        ];
    }

    /**
     * Devuelve el método HTTP del request.
     */
    protected function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Verifica que el método sea el esperado.
     */
    protected function requireMethod(string ...$methods): void
    {
        if (!in_array($this->method(), $methods, true)) {
            ApiResponse::methodNotAllowed($methods);
        }
    }
}
