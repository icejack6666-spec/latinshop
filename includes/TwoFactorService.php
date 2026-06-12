<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class TwoFactorService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // 2FA por email
    // =========================================================================

    /**
     * Genera y envía un código 2FA por email.
     */
    public function send2FACode(int $user_id): array
    {
        $user = $this->db->fetch(
            "SELECT email, username FROM users WHERE id = ? LIMIT 1",
            [$user_id]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Usuario no encontrado.'];
        }

        // Invalidar códigos previos no usados
        $this->db->update(
            "UPDATE verification_codes SET used = 1
             WHERE user_id = ? AND type = 'two_fa' AND used = 0",
            [$user_id]
        );

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->db->insert(
            "INSERT INTO verification_codes (user_id, code, type, created_at, expires_at, used)
             VALUES (?, ?, 'two_fa', NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)",
            [$user_id, $code]
        );

        $sent = Mailer::send2FACode($user['email'], $user['username'], $code);

        if (!$sent) {
            if (defined('ENV') && ENV === 'development') {
                error_log('[2FA DEV] Email falló — código: ' . $code . ' user_id=' . $user_id);
                $_SESSION['2fa_pending']  = true;
                $_SESSION['2fa_user_id']  = $user_id;
                $_SESSION['2fa_time']     = time();
                $_SESSION['2fa_dev_code'] = $code;
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'No se pudo enviar el email de verificación. Revisa tu conexión o contacta soporte.'];
        }

        $_SESSION['2fa_pending']  = true;
        $_SESSION['2fa_user_id']  = $user_id;
        $_SESSION['2fa_time']     = time();
        unset($_SESSION['2fa_dev_code']);

        return ['success' => true];
    }

    /**
     * Verifica el código 2FA (email o TOTP) y, si es correcto, devuelve los
     * datos del usuario para completar el inicio de sesión.
     */
    public function verify2FA(string $code): array
    {
        if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_user_id'])) {
            return ['success' => false, 'error' => 'Sesión de verificación expirada.'];
        }

        if (time() - ($_SESSION['2fa_time'] ?? 0) > 600) {
            unset($_SESSION['2fa_pending'], $_SESSION['2fa_user_id'], $_SESSION['2fa_time']);
            return ['success' => false, 'error' => 'Tiempo agotado. Vuelve a iniciar sesión.'];
        }

        $user_id = (int)$_SESSION['2fa_user_id'];
        $code    = trim($code);

        $user = $this->db->fetch(
            "SELECT id, username, email, role, avatar_url, phone, phone_verified,
                two_fa_enabled, two_fa_method, totp_enabled, totp_secret, backup_codes, created_at, last_login
            FROM users WHERE id = ? LIMIT 1",
            [$user_id]
        );

        if (!$user) {
            return ['success' => false, 'error' => 'Usuario no encontrado.'];
        }

        $method  = $user['two_fa_method'] ?? 'email';
        $success = false;

        if ($method === 'totp') {
            if (!empty($user['totp_secret'])) {
                $success = TOTP::verify($this->decryptTotp($user['totp_secret']), $code);
            }

            // Intentar con código de respaldo
            if (!$success && !empty($user['backup_codes'])) {
                $backups = json_decode($user['backup_codes'], true) ?? [];
                $idx     = TOTP::verifyBackupCode($code, $backups);
                if ($idx !== false) {
                    unset($backups[$idx]);
                    $this->db->update(
                        "UPDATE users SET backup_codes = ? WHERE id = ?",
                        [json_encode(array_values($backups)), $user_id]
                    );
                    $success = true;
                }
            }
        } else {
            $registro = $this->db->fetch(
                "SELECT * FROM verification_codes
                 WHERE user_id = ? AND type = 'two_fa' AND used = 0
                 AND expires_at > NOW()
                 ORDER BY created_at DESC LIMIT 1",
                [$user_id]
            );
            if ($registro && hash_equals($registro['code'], $code)) {
                $this->db->update(
                    "UPDATE verification_codes SET used = 1 WHERE id = ?",
                    [$registro['id']]
                );
                $success = true;
            }
        }

        if (!$success) {
            return ['success' => false, 'error' => 'Código incorrecto o expirado.'];
        }

        unset($_SESSION['2fa_pending'], $_SESSION['2fa_user_id'], $_SESSION['2fa_time'], $_SESSION['2fa_dev_code']);

        return ['success' => true, 'user' => $user];
    }

    // =========================================================================
    // Configuración de 2FA
    // =========================================================================

    public function toggle2FA(int $user_id, bool $activar): bool
    {
        $method = $activar ? 'email' : 'none';
        $rows   = $this->db->update(
            "UPDATE users SET two_fa_enabled = ?, two_fa_method = ? WHERE id = ?",
            [$activar ? 1 : 0, $method, $user_id]
        );
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $user_id) {
            $_SESSION['two_fa_enabled'] = $activar;
        }
        return $rows > 0;
    }

    public function set2FAMethod(int $user_id, string $method): bool
    {
        if (!in_array($method, ['none', 'email', 'totp'], true)) return false;
        $enabled = ($method !== 'none') ? 1 : 0;
        return $this->db->update(
            "UPDATE users SET two_fa_method = ?, two_fa_enabled = ? WHERE id = ?",
            [$method, $enabled, $user_id]
        ) !== false;
    }

    public function has2FA(int $user_id): bool
    {
        $user = $this->db->fetch(
            "SELECT two_fa_enabled, two_fa_method FROM users WHERE id = ? LIMIT 1",
            [$user_id]
        );
        return $user && $user['two_fa_enabled'] && $user['two_fa_method'] !== 'none';
    }

    public function get2FAMethod(int $user_id): string
    {
        $user = $this->db->fetch("SELECT two_fa_method FROM users WHERE id = ? LIMIT 1", [$user_id]);
        return $user['two_fa_method'] ?? 'none';
    }

    // =========================================================================
    // TOTP
    // =========================================================================

    public function setupTOTP(int $user_id): array
    {
        $user = $this->db->fetch("SELECT email, username FROM users WHERE id = ? LIMIT 1", [$user_id]);
        if (!$user) return ['success' => false, 'error' => 'Usuario no encontrado.'];

        $secret  = TOTP::generateSecret();
        $backups = TOTP::generateBackupCodes(8);
        $qr_url  = TOTP::getQRUrl($secret, $user['email'], SITE_NAME);

        $_SESSION['totp_setup_secret']  = $secret;
        $_SESSION['totp_setup_backups'] = $backups;
        $_SESSION['totp_setup_user']    = $user_id;

        return [
            'success' => true,
            'secret'  => $secret,
            'qr_url'  => $qr_url,
            'backups' => $backups,
        ];
    }

    public function confirmTOTP(int $user_id, string $code): array
    {
        if (empty($_SESSION['totp_setup_secret']) || (int)($_SESSION['totp_setup_user'] ?? 0) !== $user_id) {
            return ['success' => false, 'error' => 'Sesión de configuración expirada.'];
        }

        $secret = $_SESSION['totp_setup_secret'];

        if (!TOTP::verify($secret, $code)) {
            return ['success' => false, 'error' => 'Código incorrecto. Asegúrate de escanear el QR correctamente.'];
        }

        $backups = $_SESSION['totp_setup_backups'] ?? TOTP::generateBackupCodes(8);

        $this->db->update(
            "UPDATE users SET totp_secret = ?, totp_enabled = 1, two_fa_method = 'totp',
             two_fa_enabled = 1, backup_codes = ? WHERE id = ?",
            [
                $this->encryptTotp($secret),
                json_encode($backups),
                $user_id,
            ]
        );

        unset($_SESSION['totp_setup_secret'], $_SESSION['totp_setup_backups'], $_SESSION['totp_setup_user']);

        return ['success' => true, 'backups' => $backups];
    }

    public function disableTOTP(int $user_id): bool
    {
        return $this->db->update(
            "UPDATE users SET totp_secret = NULL, totp_enabled = 0,
             two_fa_method = 'none', two_fa_enabled = 0, backup_codes = NULL
             WHERE id = ?",
            [$user_id]
        ) !== false;
    }

    // =========================================================================
    // Verificación de teléfono (SMS)
    // =========================================================================

    public function sendPhoneVerification(int $user_id, string $phone): array
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = '+52' . $phone;
        }

        if (strlen($phone) < 10) {
            return ['success' => false, 'error' => 'Número de teléfono no válido.'];
        }

        $existe = $this->db->fetch(
            "SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1",
            [$phone, $user_id]
        );
        if ($existe) {
            return ['success' => false, 'error' => 'Ese número de teléfono ya está registrado en otra cuenta.'];
        }

        $this->db->update("UPDATE users SET phone = ? WHERE id = ?", [$phone, $user_id]);

        $this->db->update(
            "UPDATE verification_codes SET used = 1
             WHERE user_id = ? AND type = 'phone' AND used = 0",
            [$user_id]
        );

        $code = Twilio::generateCode();

        $this->db->insert(
            "INSERT INTO verification_codes (user_id, code, type, created_at, expires_at, used)
             VALUES (?, ?, 'phone', NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)",
            [$user_id, $code]
        );

        $twilio = Twilio::getInstance();
        $result = $twilio->sendVerificationCode($phone, $code);

        if (!$result['success']) {
            return ['success' => false, 'error' => 'No se pudo enviar el SMS. Verifica el número e intenta de nuevo.'];
        }

        return ['success' => true];
    }

    public function verifyPhone(int $user_id, string $code): array
    {
        $code = trim($code);

        $registro = $this->db->fetch(
            "SELECT * FROM verification_codes
             WHERE user_id = ? AND type = 'phone' AND used = 0
             AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1",
            [$user_id]
        );

        if (!$registro) {
            return ['success' => false, 'error' => 'El código no es válido o ya expiró. Solicita uno nuevo.'];
        }

        if ($registro['code'] !== $code) {
            return ['success' => false, 'error' => 'Código incorrecto. Verifica e intenta de nuevo.'];
        }

        $this->db->update("UPDATE verification_codes SET used = 1 WHERE id = ?", [$registro['id']]);
        $this->db->update("UPDATE users SET phone_verified = 1 WHERE id = ?", [$user_id]);

        $_SESSION['phone_verified'] = true;

        return ['success' => true];
    }

    public function resendPhoneCode(int $user_id): array
    {
        $intentos = $this->db->count(
            "SELECT COUNT(*) FROM verification_codes
             WHERE user_id = ? AND type = 'phone'
             AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
            [$user_id]
        );

        if ($intentos >= 3) {
            return ['success' => false, 'error' => 'Demasiados intentos. Espera 30 minutos antes de solicitar otro código.'];
        }

        $user = $this->db->fetch("SELECT phone FROM users WHERE id = ? LIMIT 1", [$user_id]);

        if (!$user || empty($user['phone'])) {
            return ['success' => false, 'error' => 'No hay teléfono registrado para este usuario.'];
        }

        return $this->sendPhoneVerification($user_id, $user['phone']);
    }

    // =========================================================================
    // Helpers de cifrado TOTP
    // =========================================================================

    private function encryptTotp(string $secret): string
    {
        $key = hash('sha256', $_ENV['APP_KEY'] ?? '', true);
        $iv  = random_bytes(16);
        $enc = openssl_encrypt($secret, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $enc);
    }

    private function decryptTotp(string $encrypted): string
    {
        $key  = hash('sha256', $_ENV['APP_KEY'] ?? '', true);
        $data = base64_decode($encrypted);
        $iv   = substr($data, 0, 16);
        $enc  = substr($data, 16);
        return openssl_decrypt($enc, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}
