<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

class TOTP
{
    private const DIGITS    = 6;
    private const PERIOD    = 30;   // segundos
    private const ALGORITHM = 'sha1';
    private const WINDOW    = 1;    // ±1 periodo de tolerancia (±30s)

    public static function generateSecret(): string
    {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $bytes  = random_bytes(20); // 160 bits
        for ($i = 0; $i < 20; $i++) {
            $secret .= $chars[ord($bytes[$i]) & 31];
        }
        return $secret;
    }

    public static function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s/', '', $code);
        if (strlen($code) !== self::DIGITS) return false;
        if (!ctype_digit($code)) return false;

        $timestamp = (int)floor(time() / self::PERIOD);

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (hash_equals(self::generate($secret, $timestamp + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function generate(string $secret, ?int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = (int)floor(time() / self::PERIOD);
        }

        $key     = self::base32Decode($secret);
        $time    = pack('N*', 0) . pack('N*', $timestamp);
        $hash    = hash_hmac(self::ALGORITHM, $time, $key, true);
        $offset  = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code    = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            ( ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function getQRUrl(string $secret, string $email, string $issuer = 'Latin Shop'): string
    {
        $issuer  = rawurlencode($issuer);
        $account = rawurlencode($email);
        $uri     = "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";

        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&ecc=M&data=' . rawurlencode($uri);
    }

    public static function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))); // ej: A1B2C3D4
        }
        return $codes;
    }

    public static function verifyBackupCode(string $input, array $codes): int|false
    {
        $input = strtoupper(trim(str_replace('-', '', $input)));
        foreach ($codes as $idx => $code) {
            if (hash_equals(strtoupper($code), $input)) {
                return $idx; // retorna el índice para poder eliminarlo
            }
        }
        return false;
    }

    private static function base32Decode(string $input): string
    {
        $map    = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $input  = strtoupper($input);
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            if ($char === '=') break;
            if (!isset($map[$char])) continue;
            $buffer   = ($buffer << 5) | $map[$char];
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output   .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }
}
