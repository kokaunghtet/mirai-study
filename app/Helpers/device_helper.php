<?php

if (! function_exists('parse_user_device')) {
    /**
     * Parse the User-Agent string and return a human-readable device label
     * such as "Chrome on Windows", "Safari on iPhone", or "Firefox on Linux".
     */
    function parse_user_device(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return 'Unknown device';
        }

        $browser = 'Unknown browser';
        $os = 'Unknown OS';

        // ── Browser detection (order matters — some UAs contain multiple tokens) ──
        if (str_contains($userAgent, 'Edg/')) {
            $browser = 'Edge';
        } elseif (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')) {
            $browser = 'Opera';
        } elseif (str_contains($userAgent, 'Chrome/') && ! str_contains($userAgent, 'Edg/')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox/')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/')) {
            $browser = 'Safari';
        }

        // ── OS detection ──
        if (preg_match('/\bWindows NT 1[0-9]\./i', $userAgent)) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (preg_match('/iPhone OS \d+_/i', $userAgent)) {
            $os = 'iPhone';
        } elseif (str_contains($userAgent, 'iPad')) {
            $os = 'iPad';
        } elseif (str_contains($userAgent, 'Mac OS X')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        }

        return $browser.' on '.$os;
    }
}
