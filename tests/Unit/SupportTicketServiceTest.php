<?php

declare(strict_types=1);

namespace LatinShop\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * SupportTicketServiceTest
 *
 * Tests unitarios para SupportTicketService.
 * Verifica lógica de negocio (validaciones, badges, formatters)
 * sin necesidad de base de datos real.
 */
final class SupportTicketServiceTest extends TestCase
{
    // ─── statusBadge() ───────────────────────────────────────────────────────

    public function testStatusBadgeOpen(): void
    {
        $html = \SupportTicketService::statusBadge('open');
        $this->assertStringContainsString('badge--open', $html);
        $this->assertStringContainsString('Abierto', $html);
    }

    public function testStatusBadgePending(): void
    {
        $html = \SupportTicketService::statusBadge('pending');
        $this->assertStringContainsString('badge--pending', $html);
        $this->assertStringContainsString('Pendiente', $html);
    }

    public function testStatusBadgeAnswered(): void
    {
        $html = \SupportTicketService::statusBadge('answered');
        $this->assertStringContainsString('badge--answered', $html);
        $this->assertStringContainsString('Respondido', $html);
    }

    public function testStatusBadgeClosed(): void
    {
        $html = \SupportTicketService::statusBadge('closed');
        $this->assertStringContainsString('badge--closed', $html);
        $this->assertStringContainsString('Cerrado', $html);
    }

    public function testStatusBadgeUnknownFallsBack(): void
    {
        $html = \SupportTicketService::statusBadge('desconocido');
        $this->assertStringContainsString('support-badge', $html);
        $this->assertStringContainsString('desconocido', $html);
    }

    public function testStatusBadgeEscapesXss(): void
    {
        $html = \SupportTicketService::statusBadge('<script>xss</script>');
        $this->assertStringNotContainsString('<script>', $html);
    }

    // ─── priorityBadge() ─────────────────────────────────────────────────────

    public function testPriorityBadgeLow(): void
    {
        $html = \SupportTicketService::priorityBadge('low');
        $this->assertStringContainsString('badge--low', $html);
        $this->assertStringContainsString('Baja', $html);
    }

    public function testPriorityBadgeMedium(): void
    {
        $html = \SupportTicketService::priorityBadge('medium');
        $this->assertStringContainsString('badge--medium', $html);
        $this->assertStringContainsString('Media', $html);
    }

    public function testPriorityBadgeHigh(): void
    {
        $html = \SupportTicketService::priorityBadge('high');
        $this->assertStringContainsString('badge--high', $html);
        $this->assertStringContainsString('Alta', $html);
    }

    public function testPriorityBadgeUrgent(): void
    {
        $html = \SupportTicketService::priorityBadge('urgent');
        $this->assertStringContainsString('badge--urgent', $html);
        $this->assertStringContainsString('Urgente', $html);
    }

    // ─── categoryLabel() ─────────────────────────────────────────────────────

    public function testCategoryLabelTechnical(): void
    {
        $this->assertSame('Técnico', \SupportTicketService::categoryLabel('technical'));
    }

    public function testCategoryLabelBilling(): void
    {
        $this->assertSame('Facturación', \SupportTicketService::categoryLabel('billing'));
    }

    public function testCategoryLabelAccount(): void
    {
        $this->assertSame('Cuenta', \SupportTicketService::categoryLabel('account'));
    }

    public function testCategoryLabelOther(): void
    {
        $this->assertSame('Otro', \SupportTicketService::categoryLabel('other'));
    }

    public function testCategoryLabelUnknownReturnsEscaped(): void
    {
        $result = \SupportTicketService::categoryLabel('<script>xss</script>');
        $this->assertStringNotContainsString('<script>', $result);
    }

    // ─── formatBytes() ───────────────────────────────────────────────────────

    public function testFormatBytesBytes(): void
    {
        $this->assertSame('512 B', \SupportTicketService::formatBytes(512));
    }

    public function testFormatBytesKilobytes(): void
    {
        $result = \SupportTicketService::formatBytes(2048);
        $this->assertStringContainsString('KB', $result);
        $this->assertStringContainsString('2', $result);
    }

    public function testFormatBytesMegabytes(): void
    {
        $result = \SupportTicketService::formatBytes(5 * 1024 * 1024);
        $this->assertStringContainsString('MB', $result);
        $this->assertStringContainsString('5', $result);
    }

    public function testFormatBytesZero(): void
    {
        $this->assertSame('0 B', \SupportTicketService::formatBytes(0));
    }

    // ─── createTicket() — validaciones ───────────────────────────────────────

    public function testCreateTicketRejectsShortSubject(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service = new \SupportTicketService();
        $result  = $service->createTicket([
            'subject'  => 'abc',         // menos de 5 chars
            'body'     => 'Descripción suficientemente larga para pasar la validación.',
            'category' => 'technical',
            'priority' => 'low',
        ], 1);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testCreateTicketRejectsLongSubject(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service = new \SupportTicketService();
        $result  = $service->createTicket([
            'subject'  => str_repeat('a', 201),
            'body'     => 'Descripción suficientemente larga para pasar la validación.',
            'category' => 'technical',
            'priority' => 'low',
        ], 1);

        $this->assertFalse($result['success']);
    }

    public function testCreateTicketRejectsShortBody(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service = new \SupportTicketService();
        $result  = $service->createTicket([
            'subject'  => 'Asunto válido OK',
            'body'     => 'Muy corto',     // menos de 20 chars
            'category' => 'billing',
            'priority' => 'medium',
        ], 1);

        $this->assertFalse($result['success']);
    }

    public function testCreateTicketRejectsInvalidCategory(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service = new \SupportTicketService();
        $result  = $service->createTicket([
            'subject'  => 'Asunto válido para el ticket',
            'body'     => 'Descripción suficientemente larga para pasar la validación.',
            'category' => 'invalid_cat',
            'priority' => 'medium',
        ], 1);

        $this->assertFalse($result['success']);
    }

    public function testCreateTicketSuccessWithValidData(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service = new \SupportTicketService();
        $result  = $service->createTicket([
            'subject'  => 'No puedo acceder a mi cuenta',
            'body'     => 'Desde ayer no puedo iniciar sesión aunque la contraseña es correcta.',
            'category' => 'account',
            'priority' => 'high',
        ], 1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ticket_id', $result);
        $this->assertIsInt($result['ticket_id']);
        $this->assertGreaterThan(0, $result['ticket_id']);
    }

    public function testCreateTicketDefaultsPriorityToMedium(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service  = new \SupportTicketService();
        $result   = $service->createTicket([
            'subject'  => 'Ticket sin prioridad explícita',
            'body'     => 'Descripción suficientemente larga para pasar la validación aquí.',
            'category' => 'other',
            'priority' => 'invalid_priority',  // debe normalizarse a 'medium'
        ], 1);

        $this->assertTrue($result['success']);

        $ticket = $service->getTicketRepo()->findById($result['ticket_id']);
        $this->assertSame('medium', $ticket['priority']);
    }

    public function testCreateTicketSetsStatusToOpen(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service = new \SupportTicketService();
        $result  = $service->createTicket([
            'subject'  => 'Ticket nuevo recién creado',
            'body'     => 'El estado inicial debe ser siempre open sin excepción.',
            'category' => 'technical',
            'priority' => 'medium',
        ], 1);

        $ticket = $service->getTicketRepo()->findById($result['ticket_id']);
        $this->assertSame('open', $ticket['status']);
    }

    // ─── addReply() — validaciones ────────────────────────────────────────────

    public function testAddReplyRejectsShortBody(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service  = new \SupportTicketService();
        $ticket   = $this->createTicket($service, 1);

        $result = $service->addReply($ticket['ticket_id'], 1, 'ok', false, false);
        $this->assertFalse($result['success']);
    }

    public function testAddReplySucceeds(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service = new \SupportTicketService();
        $ticket  = $this->createTicket($service, 1);

        $result = $service->addReply(
            $ticket['ticket_id'], 1,
            'Gracias por la respuesta, sigo teniendo el problema.',
            false, false
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message_id', $result);
    }

    public function testAddReplyChangesStatusToPending(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);
        $this->seedUser(2, 'admin');

        $service  = new \SupportTicketService();
        $ticket   = $this->createTicket($service, 1);
        $ticketId = $ticket['ticket_id'];

        // Admin responde → answered
        $service->addReply($ticketId, 2, 'Hemos revisado tu solicitud y...', true, false);
        $t = $service->getTicketRepo()->findById($ticketId);
        $this->assertSame('answered', $t['status']);

        // Usuario responde → pending
        $service->addReply($ticketId, 1, 'Gracias, pero el problema persiste aún.', false, false);
        $t = $service->getTicketRepo()->findById($ticketId);
        $this->assertSame('pending', $t['status']);
    }

    public function testAddReplyToClosedTicketFails(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service  = new \SupportTicketService();
        $ticket   = $this->createTicket($service, 1);
        $ticketId = $ticket['ticket_id'];

        $service->changeStatus($ticketId, 'closed', 1);
        $result = $service->addReply($ticketId, 1, 'Intento responder en ticket cerrado.', false, false);

        $this->assertFalse($result['success']);
    }

    // ─── changeStatus() ──────────────────────────────────────────────────────

    public function testChangeStatusValidTransition(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service  = new \SupportTicketService();
        $ticket   = $this->createTicket($service, 1);
        $ticketId = $ticket['ticket_id'];

        $result = $service->changeStatus($ticketId, 'closed', 1);
        $this->assertTrue($result['success']);

        $updated = $service->getTicketRepo()->findById($ticketId);
        $this->assertSame('closed', $updated['status']);
    }

    public function testChangeStatusInvalidStatusFails(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service  = new \SupportTicketService();
        $ticket   = $this->createTicket($service, 1);

        $result = $service->changeStatus($ticket['ticket_id'], 'nonexistent', 1);
        $this->assertFalse($result['success']);
    }

    public function testChangeStatusNonExistentTicketFails(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $service = new \SupportTicketService();
        $result  = $service->changeStatus(99999, 'closed', 1);
        $this->assertFalse($result['success']);
    }

    // ─── changePriority() ────────────────────────────────────────────────────

    public function testChangePriorityValid(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service  = new \SupportTicketService();
        $ticket   = $this->createTicket($service, 1);
        $ticketId = $ticket['ticket_id'];

        $result = $service->changePriority($ticketId, 'urgent', 1);
        $this->assertTrue($result['success']);

        $updated = $service->getTicketRepo()->findById($ticketId);
        $this->assertSame('urgent', $updated['priority']);
    }

    public function testChangePriorityInvalidFails(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);

        $service = new \SupportTicketService();
        $ticket  = $this->createTicket($service, 1);
        $result  = $service->changePriority($ticket['ticket_id'], 'super_high', 1);

        $this->assertFalse($result['success']);
    }

    // ─── assign() ────────────────────────────────────────────────────────────

    public function testAssignAdminToTicket(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);
        $this->seedUser(2, 'admin');

        $service  = new \SupportTicketService();
        $ticket   = $this->createTicket($service, 1);
        $ticketId = $ticket['ticket_id'];

        $result = $service->assign($ticketId, 2, 2);
        $this->assertTrue($result['success']);

        $updated = $service->getTicketRepo()->findById($ticketId);
        $this->assertSame('2', (string)$updated['assigned_to']);
    }

    public function testUnassignTicket(): void
    {
        \Database::loadSchema(\SupportSchema::get());
        $this->seedUser(1);
        $this->seedUser(2, 'admin');

        $service  = new \SupportTicketService();
        $ticket   = $this->createTicket($service, 1);
        $ticketId = $ticket['ticket_id'];

        $service->assign($ticketId, 2, 2);
        $service->assign($ticketId, null, 2);

        $updated = $service->getTicketRepo()->findById($ticketId);
        $this->assertNull($updated['assigned_to']);
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    protected function setUp(): void
    {
        \Database::reset();
        \Notifications::reset();
        \AuditLog::reset();
    }

    private function seedUser(int $id, string $role = 'client'): void
    {
        $db = \Database::getInstance();
        $db->insert(
            "INSERT INTO users (id, username, email, password_hash, role)
             VALUES (?, ?, ?, ?, ?)",
            [$id, "user{$id}", "user{$id}@test.com", 'hash', $role]
        );
    }

    private function createTicket(\SupportTicketService $service, int $userId): array
    {
        return $service->createTicket([
            'subject'  => 'Ticket de prueba unitaria',
            'body'     => 'Descripción suficientemente larga para pasar la validación del sistema.',
            'category' => 'technical',
            'priority' => 'medium',
        ], $userId);
    }
}

// Necesario para que SupportSchema esté disponible en este test
require_once __DIR__ . '/../Fixtures/SupportSchema.php';
