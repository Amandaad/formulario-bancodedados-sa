<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Basic hardening headers for browser-side protections.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_valido(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return is_string($sessionToken) && hash_equals($sessionToken, $token);
}

function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function telefone_valido(string $telefone): bool
{
    $numeros = preg_replace('/\D+/', '', $telefone);

    if (!is_string($numeros)) {
        return false;
    }

    $tamanho = strlen($numeros);
    return $tamanho >= 10 && $tamanho <= 11;
}
