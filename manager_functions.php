<?php
/** Shared security and database functions for the UrbanSync HR pages. */
declare(strict_types=1);

function managerStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Production sessions should use HTTPS; local XAMPP often uses plain HTTP.
    ini_set('session.use_strict_mode', '1');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

function managerDatabase(): PDO
{
    // Uses the SAME settings.php as process_eoi.php.
    $config = require __DIR__ . '/settings.php';
    $dsn = 'mysql:host=' . $config['host']
        . ';dbname=' . $config['database'] . ';charset=utf8mb4';

    return new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function managerEscape(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function managerCsrfToken(): string
{
    if (!isset($_SESSION['manager_csrf_token'])) {
        $_SESSION['manager_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['manager_csrf_token'];
}

function managerValidCsrf(mixed $submitted): bool
{
    return is_string($submitted)
        && isset($_SESSION['manager_csrf_token'])
        && hash_equals($_SESSION['manager_csrf_token'], $submitted);
}

function managerRequireLogin(): void
{
    if (!isset($_SESSION['manager_user_id']) || !is_int($_SESSION['manager_user_id'])) {
        header('Location: login.php', true, 303);
        exit;
    }
}

function managerSetMessage(string $type, string $message): void
{
    $_SESSION['manager_message'] = ['type' => $type, 'text' => $message];
}

function managerTakeMessage(): ?array
{
    $message = $_SESSION['manager_message'] ?? null;
    unset($_SESSION['manager_message']);
    return is_array($message) ? $message : null;
}
