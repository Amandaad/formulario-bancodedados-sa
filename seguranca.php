<?php

function env_carregar_arquivo(string $arquivo): void
{
    if (!is_file($arquivo) || !is_readable($arquivo)) {
        return;
    }

    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($linhas)) {
        return;
    }

    foreach ($linhas as $linha) {
        $linha = trim($linha);
        $linha = preg_replace('/^\xEF\xBB\xBF/', '', $linha) ?? $linha;

        if ($linha === '' || str_starts_with($linha, '#')) {
            continue;
        }

        $partes = explode('=', $linha, 2);
        if (count($partes) !== 2) {
            continue;
        }

        $chave = trim($partes[0]);
        $valor = trim($partes[1]);

        if ($chave === '') {
            continue;
        }

        $tamanho = strlen($valor);
        if ($tamanho >= 2) {
            $primeiro = $valor[0];
            $ultimo = $valor[$tamanho - 1];
            if (($primeiro === '"' && $ultimo === '"') || ($primeiro === "'" && $ultimo === "'")) {
                $valor = substr($valor, 1, -1);
            }
        }

        if (getenv($chave) === false) {
            putenv($chave . '=' . $valor);
            $_ENV[$chave] = $valor;
            $_SERVER[$chave] = $valor;
        }
    }
}

function env_valor(string $chave, string $padrao = ''): string
{
    $valor = getenv($chave);
    if (!is_string($valor) || $valor === '') {
        return $padrao;
    }

    return $valor;
}

env_carregar_arquivo(__DIR__ . '/.env');

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
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
    return env_valor('APP_ADMIN_USER', '');
}

function auth_hash_senha_padrao(): string
{
    return env_valor('APP_ADMIN_PASS_HASH', '');
}

function auth_configurada(): bool
{
    return auth_usuario_padrao() !== '' && auth_hash_senha_padrao() !== '';
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
    if (!auth_configurada() || auth_bloqueado()) {
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

