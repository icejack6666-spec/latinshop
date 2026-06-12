<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * IpHelper
 *
 * Resolves the real client IP behind proxies / Cloudflare and provides
 * address-validation utilities used by the rest of the Security layer.
 */
class IpHelper
{
    /**
     * Headers inspected in priority order.
     * CF-Connecting-IP is first because it is set (and validated) by
     * Cloudflare itself, making spoofing much harder than generic
     * X-Forwarded-For headers.
     */
    private const PROXY_HEADERS = [
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
    ];

    /**
     * Return the real public IP of the current request.
     * Falls back to REMOTE_ADDR when no trusted header yields a valid
     * public address.
     */
    public static function getRealIP(): string
    {
        foreach (self::PROXY_HEADERS as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }

            // X-Forwarded-For can be a comma-separated list; the leftmost
            // entry is the originating client.
            $ip = trim(explode(',', $_SERVER[$header])[0]);

            if (self::isPublicIP($ip)) {
                return $ip;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Return true when $ip is a syntactically valid, publicly-routable
     * IPv4 or IPv6 address (rejects loopback, private, and reserved ranges).
     */
    public static function isPublicIP(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Return true when $ip passes basic syntactic validation (any range).
     */
    public static function isValidIP(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * Return true for addresses that must never be blocked
     * (loopback and the unspecified address).
     */
    public static function isProtectedIP(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true);
    }
}
