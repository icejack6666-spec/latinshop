<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

use PHPMailer\PHPMailer\PHPMailer;

function secure_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', ENV === 'production' ? 1 : 0);
        ini_set('session.cookie_samesite', ENV === 'production' ? 'Strict' : 'Lax');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

        session_start();

        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > SESSION_LIFETIME) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

function set_security_headers(): void {

    if (headers_sent()) return;

    $nonce = generate_csp_nonce();

    header_remove('Content-Security-Policy');
    header_remove('Content-Security-Policy-Report-Only');

    $csp = "
    default-src 'self';
    base-uri 'self';
    object-src 'none';
    frame-ancestors 'none';
    form-action 'self';

    script-src 'self' 'nonce-{$nonce}' https://www.google.com https://www.gstatic.com https://*.cloudflare.com https://static.cloudflareinsights.com;

    style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;

    font-src 'self' https://fonts.gstatic.com;

    img-src 'self' data: https: blob:;

    connect-src 'self' https:;

    frame-src https://www.google.com;

    worker-src 'self';

    manifest-src 'self';
    ";

    $csp = preg_replace('/\s+/', ' ', trim($csp));

    header("Content-Security-Policy: $csp");
}



function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }

    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function sanitize_string(string $input, int $max_length = 255): string {
    $input = trim($input);
    $input = stripslashes($input);
    // No aplicar htmlspecialchars aquí — ese escape es para output HTML, no para DB
    return substr($input, 0, $max_length);
}

function sanitize_email(string $email): string|false {
    $email = trim(strtolower($email));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

function sanitize_int(mixed $value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int|false {
    $filtered = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max]
    ]);
    return $filtered !== false ? (int)$filtered : false;
}

function sanitize_url(string $url): string|false {
    $url = trim($url);
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
}

function check_rate_limit(string $action, int $max = 5, int $window = 300): bool {
    $db  = Database::getInstance();
    $ip  = $_SERVER['HTTP_CF_CONNECTING_IP'] 
        ?? $_SERVER['REMOTE_ADDR'] 
        ?? '0.0.0.0';
    $ip  = filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
    $key = $action . ':' . $ip;

    $db->query(
        "DELETE FROM rate_limits WHERE action_key = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)",
        [$key, $window]
    );

    $count = $db->count(
        "SELECT COUNT(*) FROM rate_limits WHERE action_key = ?",
        [$key]
    );

    if ($count >= $max) return false;

    $db->insert(
        "INSERT INTO rate_limits (action_key, ip_address, created_at) VALUES (?, ?, NOW())",
        [$key, $ip]
    );

    return true;
}
function verify_recaptcha(string $response): bool {
    if (empty($response)) return false;

    $data = [
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    curl_close($ch);

    if ($result === false) return false;

    $json = json_decode($result, true);
    return isset($json['success']) && $json['success'] === true;
}

function send_contact_email(array $data): bool {

    $name    = sanitize_string($data['name']);
    $email   = sanitize_email($data['email']);
    $message = sanitize_string($data['message'], 2000);
    $subject = sanitize_string($data['subject'] ?? 'Consulta general', 100);

    if (!$email) return false;

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST']     ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER']     ?? '';
        $mail->Password   = $_ENV['SMTP_PASS']     ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(getenv('SMTP_USER'), 'Latin Shop');
        $mail->addAddress(CONTACT_EMAIL);
        $mail->addReplyTo($email, $name);

        $mail->Subject = '[Latin Shop] ' . $subject;
        $mail->isHTML(true);
        $mail->Body =
            '<p><strong>Nombre:</strong> '  . htmlspecialchars($name)    . '</p>' .
            '<p><strong>Email:</strong> '   . htmlspecialchars($email)   . '</p>' .
            '<p><strong>Asunto:</strong> '  . htmlspecialchars($subject) . '</p>' .
            '<p><strong>Mensaje:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>';

        $mail->send();
        return true;

    } catch (\Exception $e) {
        error_log('[LatinShop] Mail error: ' . $e->getMessage());
        return false;
    }
}

function safe_redirect(string $url): never {
    $parsed = parse_url($url);

    if (isset($parsed['host']) && $parsed['host'] !== parse_url(SITE_URL, PHP_URL_HOST)) {
        $url = '/';
    }

    header('Location: ' . $url, true, 302);
    exit();
}

function sanitize_and_save_image(string $tmp, string $dest, string $mime): bool {
    $img = match($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png'  => @imagecreatefrompng($tmp),
        'image/webp' => @imagecreatefromwebp($tmp),
        'image/gif'  => @imagecreatefromgif($tmp),
        default      => false
    };

    if (!$img) return false;

    $ok = match($mime) {
        'image/jpeg' => imagejpeg($img, $dest, 85),
        'image/png'  => imagepng($img, $dest, 6),
        'image/webp' => imagewebp($img, $dest, 85),
        'image/gif'  => imagegif($img, $dest),
        default      => false
    };

    imagedestroy($img);
    return $ok;
}

function generate_csp_nonce(): string {
    static $nonce = null;

    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
        $_SESSION['_csp_nonce'] = $nonce;
    }

    return $nonce;
}

function csp_nonce_attr(): string {
    return 'nonce="' . htmlspecialchars(generate_csp_nonce(), ENT_QUOTES, 'UTF-8') . '"';
}