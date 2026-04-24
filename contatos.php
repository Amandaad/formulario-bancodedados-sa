<?php
require_once __DIR__ . '/seguranca.php';

$mensagem = '';
$erro = '';
$contatos = [];
$busca = trim((string) ($_GET['busca'] ?? $_POST['busca'] ?? ''));

$host = '127.0.0.1';
$banco = 'formulario';
$usuario = 'root';
$senha = '';

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $idExcluir = (int) ($_POST['id_excluir'] ?? 0);

        if (!csrf_valido($token)) {
            $erro = 'Sessao invalida. Recarregue a pagina e tente novamente.';
        } elseif ($idExcluir > 0) {
            $delete = $pdo->prepare('DELETE FROM contatos WHERE id = :id');
            $delete->execute([':id' => $idExcluir]);

            if ($delete->rowCount() > 0) {
                $mensagem = 'Contato excluido com sucesso.';
            } else {
                $erro = 'Contato nao encontrado para exclusao.';
            }
        } else {
            $erro = 'ID invalido para exclusao.';
        }
    }

    if ($busca !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, nome, telefone, email, criado_em
             FROM contatos
             WHERE nome LIKE :busca OR email LIKE :busca
             ORDER BY id DESC'
        );
        $stmt->execute([':busca' => '%' . $busca . '%']);
    } else {
        $stmt = $pdo->query('SELECT id, nome, telefone, email, criado_em FROM contatos ORDER BY id DESC');
    }

    $contatos = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Nao foi possivel carregar os contatos. Verifique se o MySQL esta ativo no XAMPP.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contatos Salvos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 24px;
        }

        .container {
            background: #fff;
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin: 0 0 18px;
            color: #222;
        }

        .acoes {
            margin-bottom: 16px;
        }

        .acoes a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .acoes a:hover {
            text-decoration: underline;
        }

        .busca {
            margin-bottom: 14px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .busca input {
            flex: 1;
            min-width: 240px;
            padding: 9px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-buscar,
        .btn-limpar {
            border: none;
            border-radius: 6px;
            padding: 9px 12px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-buscar {
            background: #1a73e8;
            color: #fff;
        }

        .btn-buscar:hover {
            background: #1765c7;
        }

        .btn-limpar {
            background: #eceff5;
            color: #333;
        }

        .btn-limpar:hover {
            background: #dde3ef;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid #e2e2e2;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f0f4ff;
            color: #222;
        }

        .erro {
            margin-top: 12px;
            padding: 10px;
            border-radius: 6px;
            background: #ffd8d8;
            color: #9f1d1d;
            font-size: 14px;
        }

        .sucesso {
            margin-top: 12px;
            padding: 10px;
            border-radius: 6px;
            background: #d1f7df;
            color: #0f7a36;
            font-size: 14px;
        }

        .vazio {
            margin-top: 12px;
            font-size: 14px;
            color: #555;
        }

        .acoes-linha {
            white-space: nowrap;
        }

        .grupo-acoes {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-editar,
        .btn-excluir {
            border: none;
            border-radius: 5px;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            color: #fff;
        }

        .btn-editar {
            background: #1a73e8;
        }

        .btn-editar:hover {
            background: #1765c7;
        }

        .btn-excluir {
            background: #d93025;
        }

        .btn-excluir:hover {
            background: #b3261e;
        }

        .form-inline {
            margin: 0;
        }

        @media (max-width: 700px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 14px;
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contatos Salvos</h1>

        <div class="acoes">
            <a href="index.php">Voltar para o formulario</a>
        </div>

        <form class="busca" method="GET">
            <input
                type="text"
                name="busca"
                placeholder="Buscar por nome ou email"
                value="<?= e($busca) ?>"
            >
            <button type="submit" class="btn-buscar">Buscar</button>
            <?php if ($busca !== ''): ?>
                <a class="btn-limpar" href="contatos.php">Limpar</a>
            <?php endif; ?>
        </form>

        <?php if ($mensagem): ?>
            <div class="sucesso"><?= e($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="erro"><?= e($erro) ?></div>
        <?php elseif (!$contatos): ?>
            <div class="vazio">Nenhum contato cadastrado ainda.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Criado em</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contatos as $contato): ?>
                        <tr>
                            <td><?= (int) $contato['id'] ?></td>
                            <td><?= e($contato['nome']) ?></td>
                            <td><?= e($contato['telefone']) ?></td>
                            <td><?= e($contato['email']) ?></td>
                            <td><?= e($contato['criado_em']) ?></td>
                            <td class="acoes-linha">
                                <div class="grupo-acoes">
                                    <a class="btn-editar" href="editar.php?id=<?= (int) $contato['id'] ?>&busca=<?= urlencode($busca) ?>">Editar</a>
                                    <form class="form-inline" method="POST" onsubmit="return confirm('Deseja excluir este contato?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="id_excluir" value="<?= (int) $contato['id'] ?>">
                                        <input type="hidden" name="busca" value="<?= e($busca) ?>">
                                        <button type="submit" class="btn-excluir">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
