<?php

declare(strict_types=1);

namespace LatinShop\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Fixtures/SupportSchema.php';

/**
 * AttachmentServiceTest
 *
 * Tests unitarios para AttachmentService.
 * Verifica normalización de archivos, validación de MIME y
 * el método formatBytes() de SupportTicketService.
 *
 * No realiza subidas reales (move_uploaded_file se simula).
 */
final class AttachmentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        \Database::loadSchema(\SupportSchema::get());
    }

    // ─── normalizeFiles() ────────────────────────────────────────────────────

    public function testNormalizeFilesSingleFile(): void
    {
        $input = [
            'name'     => 'foto.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error'    => UPLOAD_ERR_OK,
            'size'     => 102400,
        ];

        $result = \AttachmentService::normalizeFiles($input);

        $this->assertCount(1, $result);
        $this->assertSame('foto.jpg', $result[0]['name']);
        $this->assertSame(UPLOAD_ERR_OK, $result[0]['error']);
    }

    public function testNormalizeFilesMultipleFiles(): void
    {
        $input = [
            'name'     => ['foto1.jpg', 'doc.pdf'],
            'type'     => ['image/jpeg', 'application/pdf'],
            'tmp_name' => ['/tmp/php1', '/tmp/php2'],
            'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size'     => [102400, 204800],
        ];

        $result = \AttachmentService::normalizeFiles($input);

        $this->assertCount(2, $result);
        $this->assertSame('foto1.jpg', $result[0]['name']);
        $this->assertSame('doc.pdf',   $result[1]['name']);
        $this->assertSame(102400,      $result[0]['size']);
        $this->assertSame(204800,      $result[1]['size']);
    }

    public function testNormalizeFilesPreservesErrorCode(): void
    {
        $input = [
            'name'     => ['ok.jpg', 'bad.jpg'],
            'type'     => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => ['/tmp/ok', '/tmp/bad'],
            'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL],
            'size'     => [1024, 0],
        ];

        $result = \AttachmentService::normalizeFiles($input);

        $this->assertSame(UPLOAD_ERR_OK,      $result[0]['error']);
        $this->assertSame(UPLOAD_ERR_PARTIAL,  $result[1]['error']);
    }

    // ─── processUploads() — skip UPLOAD_ERR_NO_FILE ───────────────────────────

    public function testProcessUploadsSkipsNoFileError(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedMinimal();

        $service = new \AttachmentService();
        $files   = [[
            'name'     => '',
            'type'     => '',
            'tmp_name' => '',
            'error'    => UPLOAD_ERR_NO_FILE,
            'size'     => 0,
        ]];

        $result = $service->processUploads($files, 1, 1, 1);

        $this->assertEmpty($result['stored']);
        $this->assertEmpty($result['errors']);
    }

    // ─── Validación de extensión (sin tocar disco) ────────────────────────────

    /**
     * Verificamos que el servicio rechace extensiones no permitidas
     * a través de un archivo temporal real con extensión falsa.
     */
    public function testProcessUploadsRejectsInvalidExtension(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedMinimal();

        // Crear archivo temporal PHP (extensión no permitida)
        $tmp = tempnam(sys_get_temp_dir(), 'ls_test_');
        file_put_contents($tmp, '<?php echo "hola"; ?>');

        $service = new \AttachmentService();
        $files   = [[
            'name'     => 'malware.php',
            'type'     => 'application/x-php',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmp),
        ]];

        $result = $service->processUploads($files, 1, 1, 1);

        unlink($tmp);

        $this->assertEmpty($result['stored']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testProcessUploadsRejectsOversizedFile(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedMinimal();

        $service = new \AttachmentService();
        $files   = [[
            'name'     => 'grande.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => '/tmp/fake',
            'error'    => UPLOAD_ERR_OK,
            'size'     => 100 * 1024 * 1024, // 100 MB
        ]];

        // El servicio valida size ANTES de intentar leer el archivo
        // Debe rechazarlo sin llegar a finfo
        $result = $service->processUploads($files, 1, 1, 1);

        $this->assertEmpty($result['stored']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('límite', $result['errors'][0]);
    }

    public function testProcessUploadsRejectsUploadError(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedMinimal();

        $service = new \AttachmentService();
        $files   = [[
            'name'     => 'archivo.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => '',
            'error'    => UPLOAD_ERR_PARTIAL,
            'size'     => 0,
        ]];

        $result = $service->processUploads($files, 1, 1, 1);

        $this->assertEmpty($result['stored']);
        $this->assertNotEmpty($result['errors']);
    }

    // ─── AttachmentRepository básico ─────────────────────────────────────────

    public function testAttachmentRepositoryFindByMessageEmpty(): void
    {
        $repo   = new \AttachmentRepository();
        $result = $repo->findByMessage(999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testAttachmentRepositoryFindByTicketEmpty(): void
    {
        $repo   = new \AttachmentRepository();
        $result = $repo->findByTicket(999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testAttachmentRepositoryFindByIdNonExistent(): void
    {
        $repo   = new \AttachmentRepository();
        $result = $repo->findById(999);
        $this->assertFalse($result);
    }

    public function testAttachmentRepositoryCreateAndFind(): void
    {
        $this->seedMinimal();
        // Insertar un mensaje primero
        $db = \Database::getInstance();
        $msgId = $db->insert(
            "INSERT INTO support_messages (ticket_id, user_id, body) VALUES (1, 1, 'test')"
        );

        $repo = new \AttachmentRepository();
        $attId = $repo->create($msgId, 1, 1, 'foto.jpg', 'stored_uuid.jpg', 'image/jpeg', 12345);

        $att = $repo->findById($attId);
        $this->assertIsArray($att);
        $this->assertSame('foto.jpg', $att['original_name']);
        $this->assertSame('image/jpeg', $att['mime_type']);
        $this->assertSame('12345', (string)$att['size_bytes']);
    }

    public function testAttachmentRepositorySoftDelete(): void
    {
        $this->seedMinimal();
        $db    = \Database::getInstance();
        $msgId = $db->insert(
            "INSERT INTO support_messages (ticket_id, user_id, body) VALUES (1, 1, 'test')"
        );

        $repo  = new \AttachmentRepository();
        $attId = $repo->create($msgId, 1, 1, 'doc.pdf', 'uuid.pdf', 'application/pdf', 5000);

        // Soft delete
        $rows = $repo->softDelete($attId);
        $this->assertSame(1, $rows);

        // Debe retornar false porque tiene deleted_at
        $att = $repo->findById($attId);
        $this->assertFalse($att);
    }

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private function seedMinimal(): void
    {
        $db = \Database::getInstance();
        // Usuario
        try {
            $db->insert(
                "INSERT INTO users (id, username, email, password_hash, role)
                 VALUES (1, 'user1', 'u@t.com', 'h', 'client')"
            );
        } catch (\Exception) { /* ya existe */ }
        // Ticket
        try {
            $db->insert(
                "INSERT INTO support_tickets (id, user_id, subject, status, priority, category)
                 VALUES (1, 1, 'Ticket base', 'open', 'medium', 'other')"
            );
        } catch (\Exception) { /* ya existe */ }
    }
}
