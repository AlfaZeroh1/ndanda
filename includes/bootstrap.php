<?php
/**
 * Loaded by every page: config, session, helpers, CSRF protection.
 * Define NO_CSRF before including for machine endpoints (M-Pesa callbacks).
 */
require_once __DIR__ . '/../config.php';

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

ob_start();

if (session_status() === PHP_SESSION_NONE && !defined('NO_SESSION')) {
    session_name('ndanda_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

if (!file_exists(__DIR__ . '/../database/install.lock') && !defined('INSTALLING')) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/install.php');
    exit;
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/mpesa.php';

if (!defined('NO_CSRF') && !defined('INSTALLING')) {
    csrf_check();
}
