<?php
require_once __DIR__ . '/seguranca.php';

$erro = '';
$mensagem = '';
$usuario = trim((string) ($_POST['usuario'] ?? ''));
$redirect = auth_destino_seguro($_GET['redirect'] ?? $_POST['redirect'] ?? 'contatos.php');

if (auth_esta_logado()) {
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $mensagem = 'Voce saiu com sucesso.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $senha = (string) ($_POST['senha'] ?? '');

    if (!csrf_valido($token)) {
        $erro = 'Sessao invalida. Recarregue a pagina e tente novamente.';
    } elseif (!auth_configurada()) {
        $erro = 'Login desativado: configure APP_ADMIN_USER e APP_ADMIN_PASS_HASH no .env.';
    } elseif (auth_bloqueado()) {
        $erro = 'Muitas tentativas. Aguarde ' . auth_segundos_bloqueio_restante() . ' segundos.';
    } elseif ($usuario === '' || $senha === '') {
        $erro = 'Preencha usuario e senha.';
    } elseif (auth_realizar_login($usuario, $senha)) {
        header('Location: ' . $redirect);
        exit;
    } elseif (auth_bloqueado()) {
        $erro = 'Muitas tentativas. Aguarde ' . auth_segundos_bloqueio_restante() . ' segundos.';
    } else {
        $erro = 'Usuario ou senha invalidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: #fff;
            width: 100%;
            max-width: 420px;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin: 0 0 16px;
            color: #222;
        }

        p {
            margin: 0;
            color: #555;
            font-size: 14px;
        }

        label {
            display: block;
            margin-top: 12px;
            margin-bottom: 6px;
            color: #333;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }

        button {
            margin-top: 16px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #1a73e8;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1765c7;
        }

        .mensagem,
        .erro {
            margin-top: 12px;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }

        .mensagem {
            background: #d1f7df;
            color: #0f7a36;
        }

        .erro {
            background: #ffd8d8;
            color: #9f1d1d;
        }

        .links {
            margin-top: 14px;
            text-align: center;
        }

        .links a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .credenciais {
            margin-top: 12px;
            padding: 10px;
            border-radius: 6px;
            background: #eef5ff;
            color: #1b4b91;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Entrar</h1>
        <p>Area protegida de contatos.</p>

        <?php if ($mensagem): ?>
            <div class="mensagem"><?= e($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="erro"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" autocomplete="username" value="<?= e($usuario) ?>">

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" autocomplete="current-password">

            <button type="submit">Entrar</button>
        </form>

        <div class="credenciais">
            Configure as credenciais no arquivo <strong>.env</strong>:<br>
            APP_ADMIN_USER e APP_ADMIN_PASS_HASH.
        </div>

        <div class="links">
            <a href="index.php">Voltar para formulario</a>
        </div>
    </div>
</body>
</html>
