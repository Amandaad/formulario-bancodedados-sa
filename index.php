<?php
$mensagem = '';
$erro = '';

$host = '127.0.0.1';
$banco = 'formulario';
$usuario = 'root';
$senha = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nome === '' || $telefone === '' || $email === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um email valido.';
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $usuario, $senha, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$banco` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$banco`");
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS contatos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(120) NOT NULL,
                    telefone VARCHAR(30) NOT NULL,
                    email VARCHAR(180) NOT NULL,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            $stmt = $pdo->prepare('INSERT INTO contatos (nome, telefone, email) VALUES (:nome, :telefone, :email)');
            $stmt->execute([
                ':nome' => $nome,
                ':telefone' => $telefone,
                ':email' => $email,
            ]);

            $mensagem = 'Formulario enviado e salvo no banco com sucesso!';
            $_POST = [];
        } catch (PDOException $e) {
            $erro = 'Nao foi possivel salvar no banco de dados. Verifique se o MySQL esta ativo no XAMPP.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Contato</title>
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
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 24px;
            color: #222;
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
            background: #007bff;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #0066d1;
        }

        .mensagem {
            margin-top: 12px;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }

        .sucesso {
            background: #d1f7df;
            color: #0f7a36;
        }

        .erro {
            background: #ffd8d8;
            color: #9f1d1d;
        }

        .link-lista {
            margin-top: 14px;
            text-align: center;
        }

        .link-lista a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .link-lista a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Formulario</h1>

        <form method="POST">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">

            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <button type="submit">Enviar</button>
        </form>

        <?php if ($mensagem): ?>
            <div class="mensagem sucesso"><?= $mensagem ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="mensagem erro"><?= $erro ?></div>
        <?php endif; ?>

        <div class="link-lista">
            <a href="contatos.php">Ver contatos salvos</a>
        </div>
    </div>
</body>
</html>
