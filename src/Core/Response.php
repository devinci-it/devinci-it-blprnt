<?php
namespace DevinciIT\Blprnt\Core;

class Response
{
    public static function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        // Security headers
        header('Permissions-Policy: interest-cohort=()');
        header('Referrer-Policy: no-referrer');
        header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; object-src \'none\';');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data);
    }
}
