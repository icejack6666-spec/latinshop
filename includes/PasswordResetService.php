<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class PasswordResetService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Genera un token de restablecimiento para el email dado.
     * Devuelve el token en texto plano (para enviarlo por email) o false
     * si el email no existe / está baneado.
     */
    public function createPasswordResetToken(string $email): string|false
    {
        $email = sanitize_email($email);
        if ($email === false) {
            return false;
        }

        $user = $this->db->fetch(
            "SELECT id FROM users WHERE email = ? AND role != 'banned' LIMIT 1",
            [$email]
        );

        if (!$user) return false;

        // Invalidar tokens previos no usados
        $this->db->update(
            "UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0",
            [$user['id']]
        );

        $token = bin2hex(random_bytes(32));

        $this->db->insert(
            "INSERT INTO password_resets (user_id, token, created_at, expires_at, used)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR), 0)",
            [$user['id'], $token]
        );

        return $token;
    }

    /**
     * Valida el token y actualiza la contraseña del usuario.
     */
    public function resetPassword(string $token, string $nueva_password, string $confirmar): array
    {
        if (strlen($nueva_password) < 8) {
            return ['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.'];
        }

        if ($nueva_password !== $confirmar) {
            return ['success' => false, 'error' => 'Las contraseñas no coinciden.'];
        }

        $reset = $this->db->fetch(
            "SELECT * FROM password_resets
             WHERE token = ? AND used = 0 AND expires_at > NOW()
             LIMIT 1",
            [$token]
        );

        if (!$reset) {
            return ['success' => false, 'error' => 'El enlace no es válido o ya expiró.'];
        }

        $hash = password_hash($nueva_password, PASSWORD_ARGON2ID);

        $this->db->update(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [$hash, $reset['user_id']]
        );

        $this->db->update(
            "UPDATE password_resets SET used = 1 WHERE id = ?",
            [$reset['id']]
        );

        return ['success' => true];
    }
}
