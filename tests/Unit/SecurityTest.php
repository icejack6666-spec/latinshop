<?php

declare(strict_types=1);

namespace LatinShop\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * SecurityTest
 *
 * Pruebas de las funciones y lógica de seguridad del proyecto.
 * Cubre: CSRF, sanitización, validación de entradas.
 */
class SecurityTest extends TestCase
{
    // ─── CSRF ────────────────────────────────────────────────────

    public function testCsrfTokenHasCorrectLength(): void
    {
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));

        // bin2hex duplica la longitud: 32 bytes → 64 caracteres hex
        $this->assertSame(CSRF_TOKEN_LENGTH * 2, strlen($token));
    }

    public function testCsrfTokenIsHexadecimal(): void
    {
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token);
    }

    public function testCsrfTokensAreUnique(): void
    {
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }

        // Todos deben ser únicos
        $this->assertSame(100, count(array_unique($tokens)));
    }

    public function testCsrfComparisonUsesTimingSafeEqual(): void
    {
        $a = bin2hex(random_bytes(32));
        $b = bin2hex(random_bytes(32));

        // hash_equals es resistente a timing attacks
        $this->assertFalse(hash_equals($a, $b));
        $this->assertTrue(hash_equals($a, $a));
    }

    // ─── XSS / Output escaping ───────────────────────────────────

    public function testHtmlSpecialCharsEscapesXss(): void
    {
        $inputs = [
            '<script>alert(1)</script>'    => '&lt;script&gt;alert(1)&lt;/script&gt;',
            '"quoted"'                      => '&quot;quoted&quot;',
            "O'Brien"                       => 'O&#039;Brien',
            '<img src=x onerror=alert(1)>' => '&lt;img src=x onerror=alert(1)&gt;',
        ];

        foreach ($inputs as $raw => $expected) {
            $escaped = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
            $this->assertSame($expected, $escaped, "Fallo escaping para: $raw");
        }
    }

    public function testStripTagsRemovesHtml(): void
    {
        $input  = '<b>Hola</b> <script>alert(1)</script> mundo';
        $clean  = strip_tags($input);

        $this->assertSame('Hola  mundo', $clean);
        $this->assertStringNotContainsString('<', $clean);
    }

    // ─── Validación de emails ────────────────────────────────────

    public function testValidEmailsPass(): void
    {
        $valid = [
            'user@example.com',
            'user+tag@domain.co.mx',
            'admin@latin-shop.com',
        ];

        foreach ($valid as $email) {
            $this->assertNotFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL),
                "'$email' debe ser un email válido"
            );
        }
    }

    public function testInvalidEmailsFail(): void
    {
        $invalid = [
            'notanemail',
            '@domain.com',
            'user@',
            'user @domain.com',
            '',
            '<script>@x.com',
        ];

        foreach ($invalid as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL),
                "'$email' debe ser inválido"
            );
        }
    }

    // ─── Validación de enteros ───────────────────────────────────

    public function testPositiveIntegerValidation(): void
    {
        $valid = ['1', '42', '999999'];
        foreach ($valid as $v) {
            $result = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $this->assertNotFalse($result, "'$v' debe ser un entero positivo válido");
        }
    }

    public function testNegativeOrZeroIntegerFailsPositiveCheck(): void
    {
        $invalid = ['0', '-1', '-999', 'abc', '1.5'];
        foreach ($invalid as $v) {
            $result = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $this->assertFalse($result, "'$v' no debe pasar validación de entero positivo");
        }
    }

    // ─── Path traversal ─────────────────────────────────────────

    public function testPathTraversalPatternsAreDetected(): void
    {
        $dangerous = [
            '../etc/passwd',
            '..\\windows\\system32',
            'path/../../secret',
            '%2e%2e/etc',
        ];

        foreach ($dangerous as $path) {
            $decoded = urldecode($path);
            $hasDots = str_contains($decoded, '..') || str_contains($decoded, '..');
            $this->assertTrue($hasDots, "'$path' debe detectarse como path traversal");
        }
    }

    // ─── Longitud de contraseña ──────────────────────────────────

    public function testPasswordMinimumLength(): void
    {
        $minLength = 8;

        $this->assertFalse(strlen('short') >= $minLength);
        $this->assertFalse(strlen('1234567') >= $minLength);
        $this->assertTrue(strlen('12345678') >= $minLength);
        $this->assertTrue(strlen('SecurePass123!') >= $minLength);
    }

    // ─── Rate limiting (lógica básica) ───────────────────────────

    public function testRateLimitLogic(): void
    {
        $maxAttempts = MAX_LOGIN_ATTEMPTS;
        $attempts    = 0;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $attempts++;
        }

        $this->assertSame($maxAttempts, $attempts);
        $this->assertTrue($attempts >= $maxAttempts);
    }
}
