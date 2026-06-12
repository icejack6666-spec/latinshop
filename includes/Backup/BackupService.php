<?php

declare(strict_types=1);

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class BackupService
{
    private string $backupRoot;
    private string $dbDir;
    private string $filesDir;
    private string $logDir;

    private string  $dbHost;
    private string  $dbName;
    private string  $dbUser;
    private string  $dbPass;
    private int     $retentionDays;
    private int     $maxSizeBytes;

    public function __construct()
    {
        $this->backupRoot    = dirname(ROOT_PATH) . '/storage/backups';
        $this->dbDir         = $this->backupRoot . '/db';
        $this->filesDir      = $this->backupRoot . '/files';
        $this->logDir        = $this->backupRoot . '/logs';

        $this->dbHost        = DB_HOST;
        $this->dbName        = DB_NAME;
        $this->dbUser        = DB_USER;
        $this->dbPass        = DB_PASS;
        $this->retentionDays = (int) ($_ENV['BACKUP_RETENTION_DAYS'] ?? 30);
        $this->maxSizeBytes  = (int) ($_ENV['BACKUP_MAX_SIZE_MB']    ?? 500) * 1024 * 1024;

        $this->ensureDirectories();
    }

    public function createFull(): array
    {
        $dbResult    = $this->backupDatabase();
        $filesResult = $this->backupFiles();

        $success = $dbResult['success'] && $filesResult['success'];
        $this->writeLog('full', $success, [
            'db'    => $dbResult,
            'files' => $filesResult,
        ]);

        return [
            'success'    => $success,
            'db_file'    => $dbResult['file']    ?? null,
            'files_file' => $filesResult['file'] ?? null,
            'message'    => $success
                                ? 'Backup completo creado correctamente.'
                                : 'Backup parcialmente fallido. Revisa los logs.',
        ];
    }

    public function backupDatabase(): array
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename  = "db_{$this->dbName}_{$timestamp}.sql.gz";
        $filePath  = $this->dbDir . '/' . $filename;

        try {
            if ($this->isMysqldumpAvailable()) {
                return $this->dumpWithMysqldump($filePath, $filename);
            }
            return $this->dumpWithPdo($filePath, $filename);
        } catch (\Throwable $e) {
            error_log('[BackupService] DB backup error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear backup de DB: ' . $e->getMessage()];
        }
    }

    public function backupFiles(): array
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename  = "files_{$timestamp}.tar.gz";
        $filePath  = $this->filesDir . '/' . $filename;

        $includeDirs = [
            'config',
            'includes',
            'pages',
            'frontend/assets/css',
            'frontend/assets/js',
        ];

        $excludePatterns = [
            'vendor',
            'node_modules',
            '.git',
            'storage/backups',
            'frontend/assets/images',
        ];

        try {
            if ($this->isTarAvailable()) {
                return $this->archiveWithTar($filePath, $filename, $includeDirs, $excludePatterns);
            }
            return $this->archiveWithPhar($filePath, $filename, $includeDirs, $excludePatterns);
        } catch (\Throwable $e) {
            error_log('[BackupService] Files backup error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear backup de archivos: ' . $e->getMessage()];
        }
    }

    public function listBackups(): array
    {
        $backups = [];

        $this->scanDirectory($this->dbDir,    'db',    '*.sql.gz', $backups);
        $this->scanDirectory($this->filesDir, 'files', '*.tar.gz', $backups);

        usort($backups, static fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    public function purgeOld(): array
    {
        $deleted    = 0;
        $freedBytes = 0;
        $cutoff     = time() - ($this->retentionDays * 86400);

        foreach ([$this->dbDir, $this->filesDir] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (new \DirectoryIterator($dir) as $file) {
                if ($file->isDot() || !$file->isFile()) {
                    continue;
                }
                if ($file->getMTime() < $cutoff) {
                    $freedBytes += $file->getSize();
                    unlink($file->getPathname());
                    $deleted++;
                }
            }
        }

        $this->writeLog('purge', true, [
            'deleted'     => $deleted,
            'freed_bytes' => $freedBytes,
        ]);

        return ['deleted' => $deleted, 'freed_bytes' => $freedBytes];
    }

    public function deleteBackup(string $filename, string $type): bool
    {
        $filename = basename($filename);

        $dir = match ($type) {
            'db'    => $this->dbDir,
            'files' => $this->filesDir,
            default => null,
        };

        if ($dir === null) {
            return false;
        }

        $path = $dir . '/' . $filename;

        if (!file_exists($path)) {
            return false;
        }

        return unlink($path);
    }

    public function getDownloadPath(string $filename, string $type): ?string
    {
        $filename = basename($filename);

        $dir = match ($type) {
            'db'    => $this->dbDir,
            'files' => $this->filesDir,
            default => null,
        };

        if ($dir === null) {
            return null;
        }

        $path = $dir . '/' . $filename;

        if (!file_exists($path) || !is_file($path)) {
            return null;
        }

        $real = realpath($path);
        $base = realpath($dir);

        if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $real;
    }

    public function getStats(): array
    {
        $stats = [
            'total_db'    => 0,
            'total_files' => 0,
            'size_db'     => 0,
            'size_files'  => 0,
            'oldest'      => null,
            'newest'      => null,
        ];

        $all = $this->listBackups();

        foreach ($all as $b) {
            if ($b['type'] === 'db') {
                $stats['total_db']++;
                $stats['size_db'] += $b['size'];
            } else {
                $stats['total_files']++;
                $stats['size_files'] += $b['size'];
            }
        }

        if (!empty($all)) {
            $stats['newest'] = $all[0]['created_at'];
            $stats['oldest'] = end($all)['created_at'];
        }

        return $stats;
    }

    public function getRecentLogs(int $limit = 20): array
    {
        $logFile = $this->logDir . '/backup.log';
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $lines   = array_reverse($lines);
        $entries = [];

        foreach (array_slice($lines, 0, $limit) as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }

    private function dumpWithMysqldump(string $filePath, string $filename): array
    {
        $cnfFile = tempnam(sys_get_temp_dir(), 'mysqlcnf_');
        if ($cnfFile === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal.');
        }

        try {
            $cnfContent = sprintf(
                "[client]\nhost=%s\nuser=%s\npassword=%s\n",
                escapeshellcmd($this->dbHost),
                escapeshellcmd($this->dbUser),
                $this->dbPass
            );
            file_put_contents($cnfFile, $cnfContent);
            chmod($cnfFile, 0600);

            $cmd = sprintf(
                'mysqldump --defaults-file=%s --single-transaction --quick --lock-tables=false %s 2>&1 | gzip -9 > %s',
                escapeshellarg($cnfFile),
                escapeshellarg($this->dbName),
                escapeshellarg($filePath)
            );

            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                throw new \RuntimeException('mysqldump falló: ' . implode(' ', $output));
            }

        } finally {
            if (file_exists($cnfFile)) {
                unlink($cnfFile);
            }
        }

        return [
            'success'  => true,
            'file'     => $filename,
            'size'     => (int) filesize($filePath),
            'message'  => 'Backup DB creado con mysqldump.',
        ];
    }

    private function dumpWithPdo(string $filePath, string $filename): array
    {
        $db  = Database::getInstance();
        $pdo = $db->getPdo();

        $gz = gzopen($filePath, 'wb9');
        if ($gz === false) {
            throw new \RuntimeException('No se pudo crear el archivo de backup comprimido.');
        }

        try {
            $header = sprintf(
                "-- LatinShop Backup\n-- Database: %s\n-- Date: %s\n-- Generated by BackupService (PHP PDO)\n\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET NAMES utf8mb4;\n\n",
                $this->dbName,
                date('Y-m-d H:i:s')
            );
            gzwrite($gz, $header);

            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $this->dumpTable($pdo, $gz, $table);
            }

            gzwrite($gz, "\nSET FOREIGN_KEY_CHECKS=1;\n");

        } finally {
            gzclose($gz);
        }

        return [
            'success' => true,
            'file'    => $filename,
            'size'    => (int) filesize($filePath),
            'message' => 'Backup DB creado con PDO (fallback).',
        ];
    }

    /**
     * @param resource $gz  Stream gzip abierto con gzopen()
     */
    private function dumpTable(\PDO $pdo, $gz, string $table): void
    {
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';

        gzwrite($gz, "\n-- Tabla: {$table}\nDROP TABLE IF EXISTS {$quotedTable};\n");
        $createRow = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(\PDO::FETCH_ASSOC);
        gzwrite($gz, $createRow['Create Table'] . ";\n\n");

        $offset    = 0;
        $batchSize = 500;

        while (true) {
            $rows = $pdo->query("SELECT * FROM {$quotedTable} LIMIT {$batchSize} OFFSET {$offset}")->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($rows)) {
                break;
            }

            $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';

            foreach ($rows as $row) {
                $values = array_map(
                    static function (mixed $v) use ($pdo): string {
                        if ($v === null) {
                            return 'NULL';
                        }
                        return $pdo->quote((string) $v);
                    },
                    $row
                );

                gzwrite($gz, "INSERT INTO {$quotedTable} ({$columns}) VALUES (" . implode(', ', $values) . ");\n");
            }

            $offset += $batchSize;
        }
    }


    private function archiveWithTar(
        string $filePath,
        string $filename,
        array  $includeDirs,
        array  $excludePatterns
    ): array {
        $excludeArgs = implode(' ', array_map(
            static fn(string $p) => '--exclude=' . escapeshellarg($p),
            $excludePatterns
        ));

        $includeArgs = implode(' ', array_map(
            static fn(string $d) => escapeshellarg($d),
            $includeDirs
        ));

        $cmd = sprintf(
            'tar -czf %s -C %s %s %s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg(ROOT_PATH),
            $excludeArgs,
            $includeArgs
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('tar falló: ' . implode(' ', $output));
        }

        return [
            'success' => true,
            'file'    => $filename,
            'size'    => (int) filesize($filePath),
            'message' => 'Backup de archivos creado con tar.',
        ];
    }

    private function archiveWithPhar(
        string $filePath,
        string $filename,
        array  $includeDirs,
        array  $excludePatterns
    ): array {
        $tarPath = substr($filePath, 0, -3);

        if (file_exists($tarPath)) {
            unlink($tarPath);
        }

        $phar = new \PharData($tarPath);

        foreach ($includeDirs as $relDir) {
            $absDir = ROOT_PATH . '/' . $relDir;
            if (!is_dir($absDir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                $realPath = $file->getRealPath();
                if ($realPath === false || !$file->isFile()) {
                    continue;
                }

                foreach ($excludePatterns as $pattern) {
                    if (str_contains($realPath, $pattern)) {
                        continue 2;
                    }
                }

                $relativePath = $relDir . '/' . $iterator->getSubPathname();
                $phar->addFile($realPath, $relativePath);
            }
        }

        $phar->compress(\Phar::GZ);

        if (file_exists($tarPath)) {
            unlink($tarPath);
        }

        return [
            'success' => true,
            'file'    => $filename,
            'size'    => (int) filesize($filePath),
            'message' => 'Backup de archivos creado con PharData.',
        ];
    }


    private function ensureDirectories(): void
    {
        foreach ([$this->dbDir, $this->filesDir, $this->logDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
        }

        $htaccess = $this->backupRoot . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }

    private function isMysqldumpAvailable(): bool
    {
        exec('which mysqldump 2>/dev/null', $out, $code);
        return $code === 0 && !empty($out);
    }

    private function isTarAvailable(): bool
    {
        exec('which tar 2>/dev/null', $out, $code);
        return $code === 0 && !empty($out);
    }

    private function scanDirectory(string $dir, string $type, string $pattern, array &$result): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/' . $pattern) ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }

            $size = (int) filesize($path);

            $result[] = [
                'type'        => $type,
                'filename'    => basename($path),
                'size'        => $size,
                'size_human'  => $this->formatBytes($size),
                'created_at'  => date('Y-m-d H:i:s', (int) filemtime($path)),
                'path'        => $path,
            ];
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 2) . ' GB';
        }
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    private function writeLog(string $type, bool $success, array $details): void
    {
        $logFile = $this->logDir . '/backup.log';
        $entry   = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'type'      => $type,
            'success'   => $success,
            'details'   => $details,
        ], JSON_UNESCAPED_UNICODE);

        file_put_contents($logFile, $entry . "\n", FILE_APPEND | LOCK_EX);

        if (file_exists($logFile) && filesize($logFile) > 1_048_576) {
            rename($logFile, $logFile . '.' . date('Ymd'));
        }
    }
}