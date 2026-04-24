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

function auth_usuario_padrao(): string
{
    $usuario = getenv('APP_ADMIN_USER');
    return is_string($usuario) && $usuario !== '' ? $usuario : 'admin';
}

function auth_hash_senha_padrao(): string
{
    $hash = getenv('APP_ADMIN_PASS_HASH');
    if (is_string($hash) && $hash !== '') {
        return $hash;
    }

    // Default password: admin123
    return '$2y$10$WpgNfPig2.YyXOsmnpSJmODwurpTGcK7IgQiv8S39g.LPY71FXieO';
}

function auth_esta_logado(): bool
{
    return isset($_SESSION['auth_ok']) && $_SESSION['auth_ok'] === true;
}

function auth_nome_usuario(): string
{
    return is_string($_SESSION['auth_user'] ?? null) ? $_SESSION['auth_user'] : '';
}

function auth_bloqueado(): bool
{
    $ate = (int) ($_SESSION['auth_lock_until'] ?? 0);
    return $ate > time();
}

function auth_segundos_bloqueio_restante(): int
{
    $ate = (int) ($_SESSION['auth_lock_until'] ?? 0);
    $restante = $ate - time();
    return $restante > 0 ? $restante : 0;
}

function auth_registrar_falha(): void
{
    $falhas = (int) ($_SESSION['auth_fail_count'] ?? 0);
    $falhas++;
    $_SESSION['auth_fail_count'] = $falhas;

    if ($falhas >= 5) {
        $_SESSION['auth_lock_until'] = time() + 300;
        $_SESSION['auth_fail_count'] = 0;
    }
}

function auth_resetar_falhas(): void
{
    $_SESSION['auth_fail_count'] = 0;
    $_SESSION['auth_lock_until'] = 0;
}

function auth_realizar_login(string $usuario, string $senha): bool
{
    if (auth_bloqueado()) {
        return false;
    }

    $usuarioOk = hash_equals(auth_usuario_padrao(), $usuario);
    $senhaOk = password_verify($senha, auth_hash_senha_padrao());

    if (!$usuarioOk || !$senhaOk) {
        auth_registrar_falha();
        return false;
    }

    session_regenerate_id(true);
    auth_resetar_falhas();
    $_SESSION['auth_ok'] = true;
    $_SESSION['auth_user'] = $usuario;
    $_SESSION['auth_at'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return true;
}

function auth_logout(): void
{
    $_SESSION['auth_ok'] = false;
    unset($_SESSION['auth_user'], $_SESSION['auth_at']);
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function auth_destino_seguro(?string $destino): string
{
    $fallback = 'contatos.php';

    if (!is_string($destino) || $destino === '') {
        return $fallback;
    }

    if (preg_match('/^https?:\/\//i', $destino)) {
        return $fallback;
    }

    if (str_contains($destino, "\r") || str_contains($destino, "\n")) {
        return $fallback;
    }

    $destino = ltrim($destino, '/');
    $path = parse_url($destino, PHP_URL_PATH);
    $query = parse_url($destino, PHP_URL_QUERY);

    if (!is_string($path) || $path === '') {
        return $fallback;
    }

    $permitidos = ['contatos.php', 'editar.php', 'index.php'];
    if (!in_array($path, $permitidos, true)) {
        return $fallback;
    }

    return $path . (is_string($query) && $query !== '' ? '?' . $query : '');
}

function auth_exigir_login(): void
{
    if (auth_esta_logado()) {
        return;
    }

    $atual = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'contatos.php');
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $destino = $atual . ($query !== '' ? '?' . $query : '');

    header('Location: login.php?redirect=' . urlencode(auth_destino_seguro($destino)));
    exit;
}
