<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * InputScanner
 *
 * Scans $_GET / $_POST values for XSS and SQL-injection payloads.
 *
 * Returns a structured result so the caller (Security) decides what
 * action to take — making this class independently unit-testable.
 */
class InputScanner
{
    /** @var string[] XSS detection patterns */
    private const XSS_PATTERNS = [
        '/<script[\s\S]*?>[\s\S]*?<\/script>/i',
        '/javascript\s*:/i',
        '/on\w+\s*=\s*["\'][^"\']*["\']/i',
        '/<iframe[\s\S]*?>/i',
        '/eval\s*\(/i',
        '/document\.(cookie|write|location)/i',
        '/window\.(location|open)/i',
        '/<img[^>]+src[^>]*>/i',
        '/data:\s*text\/html/i',
    ];

    /** @var string[] SQLi detection patterns */
    private const SQLI_PATTERNS = [
        '/\bUNION\b[\s\S]{0,30}\bSELECT\b/i',
        '/\bSELECT\b.+\bFROM\b/i',
        '/\b(INSERT\s+INTO|DROP\s+TABLE|DROP\s+DATABASE|TRUNCATE\s+TABLE)/i',
        '/\bDELETE\s+FROM\b/i',
        '/\bUPDATE\b.+\bSET\b/i',
        '/--\s*$/m',
        '/\/\*[\s\S]*?\*\//i',
        '/\bEXEC\b\s*\(|\bEXECUTE\b\s*\(|\bxp_\w+/i',
        '/\b(BENCHMARK|SLEEP|WAITFOR)\s*\(/i',
        "/('[^']*'\s*=\s*'[^']*'|\"[^\"]*\"\s*=\s*\"[^\"]*\"|\b1\s*=\s*1\b|\b0\s*=\s*0\b)/i",
        '/;\s*(DROP|TRUNCATE|DELETE\s+FROM|UPDATE\s+\w+\s+SET)/i',
    ];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Scan a map of field values and return the first threat found, or null.
     *
     * @param  array<string, mixed> $inputs  Typically $_GET or $_POST values.
     * @return array{type: string, value: string}|null
     */
    public function scan(array $inputs): ?array
    {
        foreach ($inputs as $value) {
            if (!is_string($value)) {
                continue;
            }

            if ($this->matchesAny(self::XSS_PATTERNS, $value)) {
                return ['type' => 'xss', 'value' => $value];
            }

            if ($this->matchesAny(self::SQLI_PATTERNS, $value)) {
                return ['type' => 'sqli', 'value' => $value];
            }
        }

        return null;
    }

    /**
     * Convenience: scan the current request ($_GET + $_POST).
     *
     * @return array{type: string, value: string}|null
     */
    public function scanCurrentRequest(): ?array
    {
        return $this->scan(array_merge(
            array_values($_GET  ?? []),
            array_values($_POST ?? [])
        ));
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Return true when $value matches at least one pattern in $patterns.
     *
     * @param string[] $patterns
     */
    private function matchesAny(array $patterns, string $value): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        return false;
    }
}
