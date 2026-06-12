<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class Twilio
{
    private string $account_sid;
    private string $auth_token;
    private string $from_number;
    private static ?Twilio $instance = null;

    private function __construct()
    {
        $this->account_sid = TWILIO_ACCOUNT_SID;
        $this->auth_token  = TWILIO_AUTH_TOKEN;
        $this->from_number = TWILIO_FROM_NUMBER;
    }

    public static function getInstance(): Twilio
    {
        if (self::$instance === null) {
            self::$instance = new Twilio();
        }
        return self::$instance;
    }


    /**
     * Envía un SMS usando la API REST de Twilio
     * Devuelve: ['success' => true, 'sid' => 'xxxxx']
     *        o: ['success' => false, 'error' => 'mensaje']
     */
    public function sendSMS(string $to, string $message): array
    {
        $to = preg_replace('/[^0-9+]/', '', $to);

        if (!str_starts_with($to, '+')) {
            $to = '+52' . $to; 
        }

        $url  = "https://api.twilio.com/2010-04-01/Accounts/{$this->account_sid}/Messages.json";

        $data = [
            'From' => $this->from_number,
            'To'   => $to,
            'Body' => $message,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST,           true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,        15);
        curl_setopt($ch, CURLOPT_USERPWD,        "{$this->account_sid}:{$this->auth_token}");
        curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('[Twilio] cURL error: ' . $curl_error);
            return ['success' => false, 'error' => 'Error de conexión al enviar SMS.'];
        }

        $json = json_decode($response, true);

        if ($http_code >= 400) {
            $msg = $json['message'] ?? 'Error desconocido de Twilio';
            error_log('[Twilio] API error ' . $http_code . ': ' . $msg);
            return ['success' => false, 'error' => 'No se pudo enviar el SMS: ' . $msg];
        }

        return [
            'success' => true,
            'sid'     => $json['sid'] ?? '',
        ];
    }


    /**
     * Envía código de verificación de teléfono
     */
    public function sendVerificationCode(string $phone, string $code): array
    {
        $message = "Latin Shop: Tu código de verificación es {$code}. Válido por 10 minutos. No lo compartas.";
        return $this->sendSMS($phone, $message);
    }

    /**
     * Envía código de doble autenticación (2FA)
     */
    public function send2FACode(string $phone, string $code): array
    {
        $message = "Latin Shop: Tu código de acceso es {$code}. Válido por 5 minutos. Si no fuiste tú, cambia tu contraseña.";
        return $this->sendSMS($phone, $message);
    }

    /**
     * Genera un código numérico aleatorio de 6 dígitos
     */
    public static function generateCode(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
