<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class AuthService
{
    private Database        $db;
    private SessionManager  $session;
    private TwoFactorService $twoFactor;

    public function __construct(
        Database         $db,
        SessionManager   $session,
        TwoFactorService $twoFactor
    ) {
        $this->db        = $db;
        $this->session   = $session;
        $this->twoFactor = $twoFactor;
    }

    // =========================================================================
    // Registro
    // =========================================================================

    public function register(
        string $username,
        string $email,
        string $password,
        string $password_confirm
    ): array {
        $username = trim($username);
        $email    = strtolower(trim($email));

        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'error' => 'El nombre de usuario debe tener entre 3 y 50 caracteres.'];
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            return ['success' => false, 'error' => 'El usuario solo puede contener letras, números, guiones y puntos.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'El email no es válido.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.'];
        }

        if ($password !== $password_confirm) {
            return ['success' => false, 'error' => 'Las contraseñas no coinciden.'];
        }

        $existeEmail = $this->db->fetch(
            "SELECT id FROM users WHERE email = ? LIMIT 1",
            [$email]
        );
        if ($existeEmail) {
            return ['success' => false, 'error' => 'Ese email ya está registrado.'];
        }

        $existeUser = $this->db->fetch(
            "SELECT id FROM users WHERE username = ? LIMIT 1",
            [$username]
        );
        if ($existeUser) {
            return ['success' => false, 'error' => 'Ese nombre de usuario ya está en uso.'];
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $userId = $this->db->insert(
            "INSERT INTO users (username, email, password_hash, role, verified, created_at)
             VALUES (?, ?, ?, 'pending', 0, NOW())",
            [$username, $email, $hash]
        );

        return ['success' => true, 'user_id' => $userId];
    }

    // =========================================================================
    // Login
    // =========================================================================

    public function login(string $email, string $password, bool $remember_me = false): array
    {
        $email = strtolower(trim($email));
        $ip    = $this->resolveIp();

        $intentosFallidos = $this->db->count(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip_address = ? AND success = 0
             AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$ip]
        );

        if ($intentosFallidos >= MAX_LOGIN_ATTEMPTS) {
            return [
                'success' => false,
                'error'   => 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.',
            ];
        }

        $user = $this->db->fetch(
            "SELECT id, username, email, password_hash, role, avatar_url,
            two_fa_enabled, two_fa_method, totp_enabled, remember_token
            FROM users WHERE email = ? LIMIT 1",
            [$email]
        );

        $exito = ($user && password_verify($password, $user['password_hash'])) ? 1 : 0;

        $this->db->insert(
            "INSERT INTO login_attempts (email, ip_address, attempted_at, success)
             VALUES (?, ?, NOW(), ?)",
            [$email, $ip, $exito]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Email o contraseña incorrectos.'];
        }

        if ($user['role'] === 'banned') {
            return ['success' => false, 'error' => 'Tu cuenta ha sido suspendida. Contacta al administrador.'];
        }

        session_regenerate_id(true);

        // Si el usuario tiene 2FA activo, no establecer sesión completa todavía
        if (!empty($user['two_fa_enabled'])) {
            $_SESSION['2fa_pending'] = true;
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['2fa_time']    = time();
            return ['success' => true, 'requires_2fa' => true, 'user_id' => $user['id']];
        }

        $this->session->writeUserSession($user);

        $this->db->update("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

        if ($remember_me) {
            $this->session->setRememberMeCookie($user['id']);
        }

        $this->session->createDbSession($user['id']);

        return ['success' => true];
    }

    // =========================================================================
    // Logout
    // =========================================================================

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->session->revokeAllDbSessions((int)$_SESSION['user_id']);

            if (isset($_COOKIE['remember_me'])) {
                $this->session->clearRememberMeCookie((int)$_SESSION['user_id']);
            }
        }

        $this->session->destroySession();
    }

    // =========================================================================
    // Remember-me
    // =========================================================================

    public function rememberMeCheck(): void
    {
        if ($this->isLoggedIn()) return;

        $user = $this->session->checkRememberMeCookie();
        if (!$user) return;

        session_regenerate_id(true);
        $this->session->writeUserSession($user);

        $this->db->update("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    }

    // =========================================================================
    // Estado de sesión / identidad
    // =========================================================================

    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    public function getUser(): array|null
    {
        if (!$this->isLoggedIn()) return null;

        return [
            'id'         => $_SESSION['user_id'],
            'username'   => $_SESSION['user_username'],
            'email'      => $_SESSION['user_email'],
            'role'       => $_SESSION['user_role'],
            'avatar'     => $_SESSION['user_avatar'],
            'avatar_url' => $_SESSION['user_avatar'],
        ];
    }

    // =========================================================================
    // Control de roles
    // =========================================================================

    public function hasRole(string $role): bool
    {
        if (!$this->isLoggedIn()) return false;
        return $_SESSION['user_role'] === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        if (!$this->isLoggedIn()) return false;
        return in_array($_SESSION['user_role'], $roles, true);
    }

    public function requireRole(string|array $role): void
    {
        if (!$this->isLoggedIn()) {
            safe_redirect(u('/login'));
        }

        $roles = is_array($role) ? $role : [$role];

        if (!in_array($_SESSION['user_role'], $roles, true)) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'No tienes permiso para acceder a esa sección.';
            safe_redirect(u('/'));
        }
    }

    public function changeRole(int $user_id, string $new_role): bool
    {
        $rolesValidos = ['pending', 'client', 'verified', 'admin', 'banned'];
        if (!in_array($new_role, $rolesValidos, true)) return false;

        $afectadas = $this->db->update(
            "UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?",
            [$new_role, $user_id]
        );

        return $afectadas > 0;
    }

    // =========================================================================
    // Helper privado
    // =========================================================================

    private function resolveIp(): string
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
           ?? $_SERVER['HTTP_X_FORWARDED_FOR']
           ?? $_SERVER['REMOTE_ADDR']
           ?? '0.0.0.0';

        return filter_var(explode(',', $ip)[0], FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
}
