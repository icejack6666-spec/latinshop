<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

/**
 * BotDetector
 *
 * Identifies requests whose User-Agent matches known vulnerability
 * scanners, web-scraping attack tools, and enumeration utilities.
 *
 * Add new signatures to BAD_BOT_SIGNATURES without touching any other
 * class. Each entry is a lowercase substring matched against the
 * lower-cased User-Agent string.
 */
class BotDetector
{
    /**
     * Lowercase substrings that identify malicious scanning tools.
     * Kept as a constant so PHP interns the array once per process.
     */
    private const BAD_BOT_SIGNATURES = [
        'sqlmap',
        'nikto',
        'nessus',
        'openvas',
        'masscan',
        'zgrab',
        'nmap',
        'dirbuster',
        'gobuster',
        'wfuzz',
        'acunetix',
        'w3af',
        'burpsuite',
        'havij',
    ];

    /**
     * Check the supplied User-Agent string for known bad-bot signatures.
     *
     * @param  string $userAgent  Raw User-Agent header value.
     * @return string|null        Matched signature, or null if clean.
     */
    public function detect(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        $ua = strtolower($userAgent);

        foreach (self::BAD_BOT_SIGNATURES as $signature) {
            if (str_contains($ua, $signature)) {
                return $signature;
            }
        }

        return null;
    }

    /**
     * Convenience: check the current request's User-Agent directly.
     *
     * @return string|null  Matched signature, or null if clean.
     */
    public function detectCurrentRequest(): ?string
    {
        return $this->detect($_SERVER['HTTP_USER_AGENT'] ?? '');
    }
}
