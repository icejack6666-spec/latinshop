<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$composer_autoload = __DIR__ . '/../vendor/autoload.php';
$manual_phpmailer  = __DIR__ . '/phpmailer/src/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private static function getSmtpUser(): string  { return $_ENV['SMTP_USER'] ?? ''; }
    private static function getSmtpPass(): string  { return $_ENV['SMTP_PASS'] ?? ''; }
    private const FROM_NAME  = SITE_NAME;

    private static function send(string $to, string $subject, string $body): bool
    {
        if (preg_match('/[\r\n]/', $to . $subject)) return false;

        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = self::getSmtpUser();
            $mail->Password   = self::getSmtpPass();
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);
            $mail->CharSet    = 'UTF-8';
            
            $mail->setFrom(self::getSmtpUser(), self::FROM_NAME);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log('[Mailer] Error al enviar email a ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }

    private static function template(string $titulo, string $contenido, string $cta_texto = '', string $cta_url = ''): string
    {
        $cta_btn = '';
        if ($cta_texto && $cta_url) {
            $cta_btn = "
            <div style='text-align:center;margin:32px 0;'>
                <a href='{$cta_url}'
                   style='background:#e8602c;color:#ffffff;text-decoration:none;
                          padding:14px 32px;border-radius:8px;font-weight:700;
                          font-size:15px;display:inline-block;'>
                    {$cta_texto}
                </a>
            </div>";
        }

        return "<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#080808;font-family:Arial,sans-serif;'>
    <div style='max-width:560px;margin:40px auto;background:#0d0d0d;border:1px solid #252525;border-radius:16px;overflow:hidden;'>
        <div style='background:#141414;padding:28px 32px;border-bottom:1px solid #252525;'>
            <p style='margin:0;font-size:18px;font-weight:700;color:#f0ede8;'>🎮 " . SITE_NAME . "</p>
            <p style='margin:4px 0 0;font-size:12px;color:#707070;'>Lords Mobile</p>
        </div>
        <div style='padding:32px;'>
            <h1 style='margin:0 0 16px;font-size:22px;font-weight:700;color:#f0ede8;'>{$titulo}</h1>
            <div style='font-size:15px;color:#9a9a9a;line-height:1.7;'>{$contenido}</div>
            {$cta_btn}
        </div>
        <div style='padding:20px 32px;border-top:1px solid #252525;background:#080808;'>
            <p style='margin:0;font-size:12px;color:#4e4e4e;text-align:center;'>
                Este mensaje fue enviado por " . SITE_NAME . " •
                <a href='" . SITE_URL . "' style='color:#e8602c;text-decoration:none;'>" . SITE_URL . "</a>
            </p>
        </div>
    </div>
</body>
</html>";
    }

    public static function sendWelcome(string $email, string $username): bool
    {
        $contenido = "
            <p>Hola <strong style='color:var(--tbt-txt-white);'>{$username}</strong>,</p>
            <p>¡Gracias por registrarte en <strong style='color:var(--tbt-jade);'>" . SITE_NAME . "</strong>!</p>
            <p>Tu cuenta ha sido creada y está pendiente de aprobación.
               Un administrador la revisará pronto y recibirás un email cuando sea aprobada.</p>
            <div style='background:var(--tbt-bg-2);border-left:3px solid var(--tbt-jade);padding:16px;margin:20px 0;border-radius:4px;'>
                <p style='margin:0;font-size:13px;color:var(--tbt-txt-base);'>
                    Mientras tanto puedes explorar el sitio y ver todo el contenido disponible.
                </p>
            </div>
        ";
        return self::send($email, 'Bienvenido a ' . SITE_NAME, self::template('Bienvenido a ' . SITE_NAME, $contenido, 'Explorar el sitio', SITE_URL));
    }

    public static function sendAccountApproved(string $email, string $username): bool
    {
        $contenido = "
            <p>Hola <strong style='color:var(--tbt-txt-white);'>{$username}</strong>,</p>
            <p>¡Buenas noticias! Tu cuenta ha sido <strong style='color:#4ade80;'>aprobada</strong>.</p>
            <p>Ahora puedes iniciar sesión y dejar comentarios en todas las páginas del sitio.</p>
            <div style='background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:8px;padding:16px;margin:20px 0;'>
                <p style='margin:0;font-size:13px;color:var(--tbt-txt-sub);'>Tu rol actual: <strong style='color:#4ade80;'>Cliente ✓</strong></p>
            </div>
        ";
        return self::send($email, '✓ Tu cuenta en ' . SITE_NAME . ' ha sido aprobada', self::template('¡Tu cuenta ha sido aprobada!', $contenido, 'Ir al sitio', SITE_URL));
    }

    public static function sendAccountVerified(string $email, string $username): bool
    {
        $contenido = "
            <p>Hola <strong style='color:var(--tbt-txt-white);'>{$username}</strong>,</p>
            <p>Tu cuenta ha recibido el estado de <strong style='color:#60a5fa;'>Verificado ✓</strong>.</p>
            <p>Tus comentarios tendrán un distintivo especial en el sitio.</p>
            <div style='background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:8px;padding:16px;margin:20px 0;'>
                <p style='margin:0;font-size:13px;color:var(--tbt-txt-sub);'>Tu rol actual: <strong style='color:#60a5fa;'>Verificado ✓</strong></p>
            </div>
        ";
        return self::send($email, '✓ Cuenta verificada en ' . SITE_NAME, self::template('¡Tu cuenta está verificada!', $contenido, 'Ver mi perfil', SITE_URL . '/perfil'));
    }

    public static function sendPhoneVerified(string $email, string $username): bool
    {
        $contenido = "
            <p>Hola <strong style='color:var(--tbt-txt-white);'>{$username}</strong>,</p>
            <p>Tu número de teléfono ha sido <strong style='color:#4ade80;'>verificado correctamente</strong>.</p>
            <p>Tu cuenta está ahora pendiente de aprobación por un administrador. Te avisaremos cuando sea aprobada.</p>
        ";
        return self::send($email, 'Teléfono verificado en ' . SITE_NAME, self::template('Teléfono verificado', $contenido, 'Ver mi cuenta', SITE_URL . '/perfil'));
    }

    public static function sendCommentApproved(string $email, string $username, string $page_slug): bool
    {
        $page_label = ucwords(str_replace(['-', '/'], [' ', ' / '], $page_slug));
        $page_url   = SITE_URL . '/' . $page_slug . '#comentarios';
        $contenido  = "
            <p>Hola <strong style='color:var(--tbt-txt-white);'>{$username}</strong>,</p>
            <p>Tu comentario en <strong style='color:var(--tbt-jade);'>{$page_label}</strong>
               ha sido <strong style='color:#4ade80;'>aprobado</strong> y ya es visible públicamente.</p>
        ";
        return self::send($email, '✓ Tu comentario en ' . SITE_NAME . ' fue aprobado', self::template('Tu comentario fue aprobado', $contenido, 'Ver mi comentario', $page_url));
    }

    public static function send2FACode(string $email, string $username, string $code): bool
    {
        $contenido = "
            <p>Hola <strong style='color:#fff;'>{$username}</strong>,</p>
            <p>Tu código de verificación en 2 pasos para <strong style='color:#e8602c;'>" . SITE_NAME . "</strong> es:</p>
            <div style='text-align:center;margin:28px 0;'>
                <span style='background:#1a1a2e;border:2px solid #e8602c;border-radius:12px;
                             padding:16px 32px;font-size:36px;font-weight:700;letter-spacing:12px;
                             color:#f5a623;font-family:monospace;display:inline-block;'>{$code}</span>
            </div>
            <p style='font-size:13px;color:#aaa;'>Este código expira en <strong>10 minutos</strong>.<br>
            Si no fuiste tú quien inició sesión, cambia tu contraseña inmediatamente.</p>
        ";
        return self::send($email, '🔐 Tu código de verificación — ' . SITE_NAME, self::template('Verificación en 2 pasos', $contenido));
    }
}