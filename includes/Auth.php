<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

require_once __DIR__ . '/Twilio.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/TwoFactorService.php';
require_once __DIR__ . '/PasswordResetService.php';
require_once __DIR__ . '/AuthService.php';

/**
 * Auth
 *
 * Facade singleton que mantiene la API pública original mientras delega
 * la lógica a los servicios especializados:
 *
 *  - AuthService          → registro, login, logout, roles, identidad
 *  - TwoFactorService     → 2FA (email / TOTP) y verificación de teléfono
 *  - SessionManager       → sesión PHP, cookie remember-me, sesiones en BD
 *  - PasswordResetService → tokens de restablecimiento y cambio de contraseña
 */
class Auth
{
    private static ?Auth $instance = null;

    private AuthService          $auth;
    private TwoFactorService     $twoFactor;
    private SessionManager       $session;
    private PasswordResetService $passwordReset;

    private function __construct()
    {
        $db = Database::getInstance();

        $this->session       = new SessionManager($db);
        $this->twoFactor     = new TwoFactorService($db);
        $this->auth          = new AuthService($db, $this->session, $this->twoFactor);
        $this->passwordReset = new PasswordResetService($db);
    }

    public static function getInstance(): Auth
    {
        if (self::$instance === null) {
            self::$instance = new Auth();
        }
        return self::$instance;
    }

    // =========================================================================
    // AuthService — proxy methods
    // =========================================================================

    public function register(string $username, string $email, string $password, string $password_confirm): array
    {
        return $this->auth->register($username, $email, $password, $password_confirm);
    }

    public function login(string $email, string $password, bool $remember_me = false): array
    {
        return $this->auth->login($email, $password, $remember_me);
    }

    public function logout(): void
    {
        $this->auth->logout();
    }

    public function rememberMeCheck(): void
    {
        $this->auth->rememberMeCheck();
    }

    public function isLoggedIn(): bool
    {
        return $this->auth->isLoggedIn();
    }

    public function getUser(): array|null
    {
        return $this->auth->getUser();
    }

    public function hasRole(string $role): bool
    {
        return $this->auth->hasRole($role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->auth->hasAnyRole($roles);
    }

    public function requireRole(string|array $role): void
    {
        $this->auth->requireRole($role);
    }

    public function changeRole(int $user_id, string $new_role): bool
    {
        return $this->auth->changeRole($user_id, $new_role);
    }

    // =========================================================================
    // TwoFactorService — proxy methods
    // =========================================================================

    public function send2FACode(int $user_id): array
    {
        return $this->twoFactor->send2FACode($user_id);
    }

    public function verify2FA(string $code): array
    {
        $result = $this->twoFactor->verify2FA($code);

        // Completar el inicio de sesión si 2FA fue exitoso
        if ($result['success'] && isset($result['user'])) {
            $user    = $result['user'];
            $user_id = (int)$user['id'];

            session_regenerate_id(true);
            $this->session->writeUserSession($user);

            Database::getInstance()->update(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user_id]
            );

            $this->session->createDbSession($user_id);

            unset($result['user']);
        }

        return $result;
    }

    public function toggle2FA(int $user_id, bool $activar): bool
    {
        return $this->twoFactor->toggle2FA($user_id, $activar);
    }

    public function set2FAMethod(int $user_id, string $method): bool
    {
        return $this->twoFactor->set2FAMethod($user_id, $method);
    }

    public function has2FA(int $user_id): bool
    {
        return $this->twoFactor->has2FA($user_id);
    }

    public function get2FAMethod(int $user_id): string
    {
        return $this->twoFactor->get2FAMethod($user_id);
    }

    public function setupTOTP(int $user_id): array
    {
        return $this->twoFactor->setupTOTP($user_id);
    }

    public function confirmTOTP(int $user_id, string $code): array
    {
        return $this->twoFactor->confirmTOTP($user_id, $code);
    }

    public function disableTOTP(int $user_id): bool
    {
        return $this->twoFactor->disableTOTP($user_id);
    }

    public function sendPhoneVerification(int $user_id, string $phone): array
    {
        return $this->twoFactor->sendPhoneVerification($user_id, $phone);
    }

    public function verifyPhone(int $user_id, string $code): array
    {
        return $this->twoFactor->verifyPhone($user_id, $code);
    }

    public function resendPhoneCode(int $user_id): array
    {
        return $this->twoFactor->resendPhoneCode($user_id);
    }

    // =========================================================================
    // SessionManager — proxy methods
    // =========================================================================

    public function getActiveSessions(int $user_id): array
    {
        return $this->session->getActiveSessions($user_id);
    }

    public function revokeSession(int $session_id, int $user_id): bool
    {
        return $this->session->revokeDbSession($session_id, $user_id);
    }

    public function revokeAllSessions(int $user_id): void
    {
        $this->session->revokeAllDbSessions($user_id);
    }

    public function setFlash(string $type, string $message): void
    {
        $this->session->setFlash($type, $message);
    }

    public function getFlash(string $type): string|null
    {
        return $this->session->getFlash($type);
    }

    // =========================================================================
    // PasswordResetService — proxy methods
    // =========================================================================

    public function createPasswordResetToken(string $email): string|false
    {
        return $this->passwordReset->createPasswordResetToken($email);
    }

    public function resetPassword(string $token, string $nueva_password, string $confirmar): array
    {
        return $this->passwordReset->resetPassword($token, $nueva_password, $confirmar);
    }
}
