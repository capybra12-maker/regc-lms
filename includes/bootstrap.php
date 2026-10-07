<?php

if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/env.php';
require_once dirname(__DIR__) . '/config/app.php';

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com https://unpkg.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://maps.googleapis.com https://maps.gstatic.com https://unpkg.com; img-src 'self' data: blob: https://images.unsplash.com https://maps.googleapis.com https://maps.gstatic.com https://streetviewpixels-pa.googleapis.com https://*.ggpht.com https://*.tile.openstreetmap.org https://unpkg.com https://api.qrserver.com; connect-src 'self' https://maps.googleapis.com https://nominatim.openstreetmap.org; frame-src https://www.google.com; form-action 'self'; base-uri 'self'; frame-ancestors 'self'");
}

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

$isHttps =
    !empty($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off';

session_name('REGC_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
