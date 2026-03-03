<?php
require_once 'includes/init.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}



$nome = $_SESSION['nome'];
$email = ''; 
$erro = '';
$sucesso = '';

try {
    $stmt = query("SELECT email FROM usuarios WHERE id = ?", [$_SESSION['usuario_id']]);
    $user = $stmt->fetch();
    $email = $user['email'] ?? '';
} catch (PDOException $e) {
    $erro = 'Erro ao carregar dados: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (empty($senha_atual) || empty($nova_senha) || empty($confirma_senha)) {
        $erro = 'Preencha todos os campos.';
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = 'As novas senhas não coincidem.';
    } elseif (strlen($nova_senha) < 6) {
        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            $stmt = query("SELECT senha FROM usuarios WHERE id = ?", [$_SESSION['usuario_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha_atual, $user['senha'])) {
                $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                query("UPDATE usuarios SET senha = ? WHERE id = ?", [$novo_hash, $_SESSION['usuario_id']]);
                $sucesso = 'Senha alterada com sucesso! Use a nova senha no próximo login.';
            } else {
                $erro = 'Senha atual incorreta.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao alterar senha: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Alterar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Controle de Estagiários</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <?php if (in_array($_SESSION['tipo'], ['admin', 'supervisor'])): ?>
                        <li class="nav-item"><a class="nav-link" href="estagiarios.php">Estagiários</a></li>
                        <li class="nav-item"><a class="nav-link" href="presencas.php">Presenças</a></li>
                        <li class="nav-item"><a class="nav-link" href="tarefas.php">Tarefas</a></li>
                        <li class="nav-item"><a class="nav-link" href="relatorios.php">Relatórios</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Meu Perfil - Alterar Senha</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-center mb-4"><strong>Nome:</strong> <?= htmlspecialchars($nome) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($email) ?></p>

                        <?php if ($erro): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>

                        <?php if ($sucesso): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="senha_atual" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                            </div>
                            <div class="mb-3">
                                <label for="nova_senha" class="form-label">Nova Senha </label>
                                <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label for="confirma_senha" class="form-label">Confirmar Nova Senha </label>
                                <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Alterar Senha</button>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-secondary">Voltar ao Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>