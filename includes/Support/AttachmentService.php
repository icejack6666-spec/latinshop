<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * AttachmentService
 *
 * Valida, mueve y registra archivos adjuntos de tickets.
 * Los archivos se guardan FUERA del directorio público en:
 *   ROOT_PATH . '/storage/support_attachments/'
 *
 * Permitidos: jpg, jpeg, png, pdf, zip
 * Tamaño máximo: SUPPORT_MAX_UPLOAD_BYTES (configurable en config.php)
 */
class AttachmentService
{
    /** Tipos MIME permitidos: extensión => mime */
    private const ALLOWED_MIME = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'pdf'  => 'application/pdf',
        'zip'  => 'application/zip',
    ];

    /** Bytes máximos por defecto (5 MB) si la constante no está definida */
    private const DEFAULT_MAX_BYTES = 5_242_880;

    private AttachmentRepository $repo;
    private string               $storageDir;
    private int                  $maxBytes;

    public function __construct()
    {
        $this->repo       = new AttachmentRepository();
        $this->storageDir = ROOT_PATH . '/storage/support_attachments/';
        $this->maxBytes   = defined('SUPPORT_MAX_UPLOAD_BYTES')
            ? SUPPORT_MAX_UPLOAD_BYTES
            : self::DEFAULT_MAX_BYTES;

        // Crear directorio si no existe (fuera de webroot)
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0750, true);
            file_put_contents($this->storageDir . '.htaccess', "Deny from all\n");
        }
    }

    /**
     * Procesa y almacena una lista de archivos subidos.
     * Devuelve ['stored' => [...], 'errors' => [...]]
     *
     * @param array  $files     Segmento de $_FILES normalizado (array de archivos)
     * @param int    $messageId ID del mensaje al que pertenecen
     * @param int    $ticketId
     * @param int    $uploadedBy
     * @return array{stored: array, errors: array}
     */
    public function processUploads(
        array $files,
        int   $messageId,
        int   $ticketId,
        int   $uploadedBy
    ): array {
        $stored = [];
        $errors = [];

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = $this->processOne($file, $messageId, $ticketId, $uploadedBy);

            if ($result['success']) {
                $stored[] = $result['attachment'];
            } else {
                $errors[] = $result['error'];
            }
        }

        return ['stored' => $stored, 'errors' => $errors];
    }

    /**
     * Normaliza $_FILES['field'] a un array uniforme de archivos individuales.
     * Soporta inputs múltiples (name="files[]") y simples (name="file").
     *
     * @param  array $filesField  $_FILES['nombre_del_campo']
     * @return array              Lista de archivos individuales
     */
    public static function normalizeFiles(array $filesField): array
    {
        $normalized = [];

        if (is_array($filesField['name'])) {
            $count = count($filesField['name']);
            for ($i = 0; $i < $count; $i++) {
                $normalized[] = [
                    'name'     => $filesField['name'][$i],
                    'type'     => $filesField['type'][$i],
                    'tmp_name' => $filesField['tmp_name'][$i],
                    'error'    => $filesField['error'][$i],
                    'size'     => $filesField['size'][$i],
                ];
            }
        } else {
            $normalized[] = $filesField;
        }

        return $normalized;
    }

    /**
     * Sirve un archivo adjunto verificando permisos previos al llamar.
     * Establece headers correctos y envía el archivo.
     */
    public function serveFile(int $attachmentId, int $userId, bool $isAdmin): void
    {
        $att = $this->repo->findById($attachmentId);

        if (!$att) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }

        // Verificar acceso: admin o dueño del ticket
        if (!$isAdmin) {
            $ticketRepo = new SupportTicketRepository();
            if (!$ticketRepo->belongsToUser((int)$att['ticket_id'], $userId)) {
                http_response_code(403);
                exit('Acceso denegado.');
            }
        }

        $filePath = $this->storageDir . $att['stored_name'];

        if (!file_exists($filePath)) {
            http_response_code(404);
            exit('Archivo no disponible.');
        }

        // Headers seguros para descarga
        $safeOriginal = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $att['original_name']);
        header('Content-Type: ' . $att['mime_type']);
        header('Content-Disposition: attachment; filename="' . $safeOriginal . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-cache');

        readfile($filePath);
        exit;
    }

    /**
     * Soft-delete de un adjunto (solo admin o dueño).
     */
    public function delete(int $attachmentId): bool
    {
        return $this->repo->softDelete($attachmentId) > 0;
    }

    // ─── PRIVADO ──────────────────────────────────────────────────────────────

    /**
     * Procesa un único archivo.
     *
     * @return array{success: bool, attachment?: array, error?: string}
     */
    private function processOne(
        array $file,
        int   $messageId,
        int   $ticketId,
        int   $uploadedBy
    ): array {
        // Error de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->uploadErrorMessage($file['error'])];
        }

        // Tamaño
        if ($file['size'] > $this->maxBytes) {
            $maxMb = round($this->maxBytes / 1_048_576, 1);
            return ['success' => false, 'error' => "El archivo supera el límite de {$maxMb} MB."];
        }

        // Extensión
        $originalName = $file['name'];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!isset(self::ALLOWED_MIME[$ext])) {
            return ['success' => false, 'error' => "Extensión «{$ext}» no permitida."];
        }

        // MIME real (finfo)
        $realMime = $this->getRealMime($file['tmp_name']);

        // Para zip permitimos variantes comunes
        $allowedMimes = [$ext === 'zip'
            ? ['application/zip', 'application/x-zip-compressed', 'application/octet-stream']
            : [self::ALLOWED_MIME[$ext]]
        ][0];

        if (!in_array($realMime, $allowedMimes, true)) {
            return ['success' => false, 'error' => "El tipo MIME real del archivo no es válido."];
        }

        // Nombre almacenado: UUID + extensión
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath   = $this->storageDir . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Error al mover el archivo. Intenta de nuevo.'];
        }

        // Persistir en BD
        $attId = $this->repo->create(
            $messageId,
            $ticketId,
            $uploadedBy,
            basename($originalName),
            $storedName,
            $realMime,
            (int)$file['size']
        );

        return [
            'success'    => true,
            'attachment' => [
                'id'            => $attId,
                'original_name' => basename($originalName),
                'stored_name'   => $storedName,
                'mime_type'     => $realMime,
                'size_bytes'    => (int)$file['size'],
            ],
        ];
    }

    private function getRealMime(string $tmpPath): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($tmpPath) ?: 'application/octet-stream';
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió de forma incompleta.',
            UPLOAD_ERR_NO_TMP_DIR => 'Error del servidor: directorio temporal no disponible.',
            UPLOAD_ERR_CANT_WRITE => 'Error del servidor: no se pudo escribir el archivo.',
            default => 'Error desconocido al subir el archivo.',
        };
    }
}
