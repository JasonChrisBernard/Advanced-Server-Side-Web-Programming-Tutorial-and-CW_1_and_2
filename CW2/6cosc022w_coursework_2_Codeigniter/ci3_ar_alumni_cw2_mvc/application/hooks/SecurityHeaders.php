<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SecurityHeaders
{
    public function set()
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; img-src 'self' data: https://unpkg.com; connect-src 'self'; font-src 'self' https://unpkg.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    }
}
