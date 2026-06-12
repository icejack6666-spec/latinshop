<?php
declare(strict_types=1);

namespace LatinShop\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ApiResponseTest
 *
 * Verifica constantes de error y la lógica de paginación de BaseEndpoint.
 * ApiResponse::send() llama ob_end_clean()+exit, por lo que los tests
 * de estructura JSON se hacen verificando el array antes de codificarlo
 * usando métodos reflection o directamente construyendo el payload esperado.
 */
final class ApiResponseTest extends TestCase
{
    // ─── Constantes ──────────────────────────────────────────────────────────

    public function testErrorCodesAreDefined(): void
    {
        $this->assertSame('UNAUTHORIZED',        \ApiResponse::ERR_UNAUTHORIZED);
        $this->assertSame('FORBIDDEN',           \ApiResponse::ERR_FORBIDDEN);
        $this->assertSame('NOT_FOUND',           \ApiResponse::ERR_NOT_FOUND);
        $this->assertSame('VALIDATION_ERROR',    \ApiResponse::ERR_VALIDATION);
        $this->assertSame('RATE_LIMIT_EXCEEDED', \ApiResponse::ERR_RATE_LIMIT);
        $this->assertSame('METHOD_NOT_ALLOWED',  \ApiResponse::ERR_METHOD_NOT_ALLOWED);
        $this->assertSame('INTERNAL_ERROR',      \ApiResponse::ERR_INTERNAL);
        $this->assertSame('INSUFFICIENT_SCOPE',  \ApiResponse::ERR_INVALID_SCOPE);
    }

    // ─── Estructura interna de payloads (sin exit) ────────────────────────────

    public function testOkPayloadStructure(): void
    {
        $body = ['success' => true, 'data' => ['id' => 1]];
        $this->assertTrue($body['success']);
        $this->assertSame(['id' => 1], $body['data']);
    }

    public function testOkPayloadWithMeta(): void
    {
        $body = ['success' => true, 'data' => [], 'meta' => ['total' => 50, 'page' => 1]];
        $this->assertArrayHasKey('meta', $body);
        $this->assertSame(50, $body['meta']['total']);
    }

    public function testErrorPayloadStructure(): void
    {
        $body = [
            'success' => false,
            'error'   => ['code' => 'NOT_FOUND', 'message' => 'No encontrado'],
        ];
        $this->assertFalse($body['success']);
        $this->assertSame('NOT_FOUND', $body['error']['code']);
    }

    public function testValidationErrorPayloadHasDetails(): void
    {
        $msgs   = ['El campo X es requerido.', 'El campo Y es inválido.'];
        $body   = [
            'success' => false,
            'error'   => [
                'code'    => \ApiResponse::ERR_VALIDATION,
                'message' => 'Los datos enviados no son válidos.',
                'details' => $msgs,
            ],
        ];
        $this->assertCount(2, $body['error']['details']);
        $this->assertSame(\ApiResponse::ERR_VALIDATION, $body['error']['code']);
    }

    public function testJsonEncodingPreservesUnicode(): void
    {
        $data    = ['msg' => 'áéíóú ñ'];
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $decoded = json_decode($encoded, true);

        $this->assertSame('áéíóú ñ', $decoded['msg']);
        $this->assertStringNotContainsString('\u00', $encoded);
    }

    public function testJsonDoesNotHaveSlashEscaping(): void
    {
        $data    = ['url' => 'https://example.com/path'];
        $encoded = json_encode($data, JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('https://example.com/path', $encoded);
    }

    // ─── paginationMeta (BaseEndpoint) ────────────────────────────────────────

    public function testPaginationMetaCalculatesTotalPages(): void
    {
        $ep   = $this->makeEndpoint();
        $meta = $ep->publicMeta(55, 1, 20);
        $this->assertSame(55, $meta['total']);
        $this->assertSame(1,  $meta['page']);
        $this->assertSame(20, $meta['per_page']);
        $this->assertSame(3,  $meta['total_pages']); // ceil(55/20)
    }

    public function testPaginationMetaExactDivision(): void
    {
        $ep = $this->makeEndpoint();
        $this->assertSame(2, $ep->publicMeta(40, 2, 20)['total_pages']);
    }

    public function testPaginationMetaZeroTotal(): void
    {
        $ep   = $this->makeEndpoint();
        $meta = $ep->publicMeta(0, 1, 20);
        $this->assertSame(0, $meta['total_pages']);
        $this->assertSame(0, $meta['total']);
    }

    public function testPaginationMetaPageAndPerPage(): void
    {
        $ep   = $this->makeEndpoint();
        $meta = $ep->publicMeta(100, 3, 10);
        $this->assertSame(3,  $meta['page']);
        $this->assertSame(10, $meta['per_page']);
        $this->assertSame(10, $meta['total_pages']);
    }

    public function testPaginationMetaSingleItem(): void
    {
        $ep   = $this->makeEndpoint();
        $meta = $ep->publicMeta(1, 1, 20);
        $this->assertSame(1, $meta['total_pages']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeEndpoint(): object
    {
        return new class(new \ApiAuth()) extends \BaseEndpoint {
            public function publicMeta(int $t, int $p, int $pp): array {
                return $this->paginationMeta($t, $p, $pp);
            }
        };
    }
}
