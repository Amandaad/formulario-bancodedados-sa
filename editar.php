<?php
$mensagem = '';
$erro = '';
$contato = null;

$host = '127.0.0.1';
$banco = 'formulario';
$usuario = 'root';
$senha = '';

$id = (int) ($_GET['id'] ?? 0);
$busca = trim($_GET['busca'] ?? '');
$linkContatos = 'contatos.php' . ($busca !== '' ? '?busca=' . urlencode($busca) : '');

if ($id <= 0) {
    $erro = 'ID invalido para edicao.';
}

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

    if (!$erro && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = trim($_POST['nome'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nome === '' || $telefone === '' || $email === '') {
            $erro = 'Preencha todos os campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Digite um email valido.';
        } else {
            $update = $pdo->prepare('UPDATE contatos SET nome = :nome, telefone = :telefone, email = :email WHERE id = :id');
            $update->execute([
                ':nome' => $nome,
                ':telefone' => $telefone,
                ':email' => $email,
                ':id' => $id,
            ]);

            if ($update->rowCount() > 0) {
                $mensagem = 'Contato atualizado com sucesso.';
            } else {
                $mensagem = 'Nenhuma alteracao foi feita.';
            }
        }
    }

    if (!$erro) {
        $stmt = $pdo->prepare('SELECT id, nome, telefone, email FROM contatos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $contato = $stmt->fetch();

        if (!$contato) {
            $erro = 'Contato nao encontrado.';
        }
    }
} catch (PDOException $e) {
    $erro = 'Nao foi possivel editar o contato. Verifique se o MySQL esta ativo no XAMPP.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Contato</title>
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
            max-width: 460px;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin: 0 0 14px;
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
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .links a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Contato</h1>

        <?php if ($mensagem): ?>
            <div class="mensagem"><?= $mensagem ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php elseif ($contato): ?>
            <form method="POST">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? $contato['nome']) ?>">

                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? $contato['telefone']) ?>">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $contato['email']) ?>">

                <button type="submit">Salvar alteracoes</button>
            </form>
        <?php endif; ?>

        <div class="links">
            <a href="<?= $linkContatos ?>">Voltar para contatos</a>
            <a href="index.php">Ir para formulario</a>
        </div>
    </div>
</body>
</html>
