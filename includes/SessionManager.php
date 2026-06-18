<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class SessionManager
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Escribe las variables de sesión estándar para un usuario autenticado.
     */
    public function writeUserSession(array $user): void
    {
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email']    = $user['email'];
        $_SESSION['user_role']     = $user['role'];
        $_SESSION['user_avatar']   = $user['avatar_url'];
        $_SESSION['logged_in']     = true;
        $_SESSION['login_time']    = time();
    }

    /**
     * Escribe una fila en la tabla `sessions` de la BD.
     */
    public function createDbSession(int $user_id): void
    {
        $ip = $this->resolveIp();

        $sessionToken = bin2hex(random_bytes(32));
        $this->db->insert(
            "INSERT INTO sessions (user_id, session_token, ip_address, user_agent, created_at, expires_at, is_active)
             VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), 1)",
            [
                $user_id,
                $sessionToken,
                $ip,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                SESSION_LIFETIME,
            ]
        );
    }

    /**
     * Emite la cookie remember_me y guarda su hash en la BD.
     */
    public function setRememberMeCookie(int $user_id): void
    {
        $token  = bin2hex(random_bytes(32));
        $hash   = hash('sha256', $token);
        $secure = (ENV === 'production');

        $this->db->update(
            "UPDATE users SET remember_token = ? WHERE id = ?",
            [$hash, $user_id]
        );

        setcookie('remember_me', $token, [
            'expires'  => time() + (30 * 24 * 60 * 60),
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    /**
     * Intenta autenticar al usuario mediante la cookie remember_me.
     * Devuelve los datos del usuario si tiene éxito, null en caso contrario.
     */
    public function checkRememberMeCookie(): array|null
    {
        if (empty($_COOKIE['remember_me'])) return null;

        $token = $_COOKIE['remember_me'];
        $hash  = hash('sha256', $token);

        $user = $this->db->fetch(
            "SELECT id, username, email, role, avatar_url, two_fa_enabled, two_fa_method
            FROM users WHERE remember_token = ? AND role != 'banned' LIMIT 1",
            [$hash]
        );

        if (!$user) {
            setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/']);
            return null;
        }

        return $user;
    }

    /**
     * Invalida la cookie remember_me (borra cookie + token en BD).
     */
    public function clearRememberMeCookie(int $user_id): void
    {
        $this->db->update(
            "UPDATE users SET remember_token = NULL WHERE id = ?",
            [$user_id]
        );

        setcookie('remember_me', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => (ENV === 'production'),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    /**
     * Destruye la sesión PHP activa.
     */
    public function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Marca todas las filas de sesión del usuario como inactivas en la BD.
     */
    public function revokeAllDbSessions(int $user_id): void
    {
        $this->db->update(
            "UPDATE sessions SET is_active = 0 WHERE user_id = ?",
            [$user_id]
        );
    }

    /**
     * Revoca una sesión específica del usuario en la BD.
     */
    public function revokeDbSession(int $session_id, int $user_id): bool
    {
        $rows = $this->db->update(
            "UPDATE sessions SET is_active = 0 WHERE id = ? AND user_id = ?",
            [$session_id, $user_id]
        );
        return $rows > 0;
    }

    /**
     * Devuelve las sesiones activas del usuario desde la BD.
     */
    public function getActiveSessions(int $user_id): array
    {
        return $this->db->fetchAll(
            "SELECT id, ip_address, user_agent, created_at, expires_at
             FROM sessions
             WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
             ORDER BY created_at DESC",
            [$user_id]
        ) ?: [];
    }

    /**
     * Almacena y recupera mensajes flash en sesión.
     */
    public function setFlash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    public function getFlash(string $type): string|null
    {
        $key = 'flash_' . $type;
        if (isset($_SESSION[$key])) {
            $msg = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $msg;
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    // DESPUÉS
    private function resolveIp(): string
    {
        return IpHelper::getRealIP();
    }
}
