<?php


require_once 'includes/init.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}



$erro = $sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $curso = trim($_POST['curso'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $inicio = $_POST['data_inicio'] ?: null;
    $fim = $_POST['data_fim'] ?: null;

    if (!$nome || !$email || !$senha || !$curso || !$departamento) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'Senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            
            $stmt = query("SELECT id FROM usuarios WHERE email = ?", [$email]);
            if ($stmt->fetch()) {
                $erro = 'Este email já está cadastrado.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);

                
                query("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'estagiario')", [$nome, $email, $hash]);
                $usuario_id = $pdo->lastInsertId();

                query("INSERT INTO estagiarios (usuario_id, curso, departamento, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?)",
                    [$usuario_id, $curso, $departamento, $inicio, $fim]);

                $sucesso = "Estagiário cadastrado! Senha temporária: <strong>$senha</strong> (avise o estagiário para alterar).";

            }
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Estagiário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Controle de Estagiários</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="cadastro.php">Cadastrar</a></li>
                <li class="nav-item"><a class="nav-link" href="estagiarios.php">Lista</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Cadastrar Novo Estagiário</h4>
                </div>
                <div class="card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?= $sucesso ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nome Completo </label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email </label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha Temporária (mín. 6 caracteres)</label>
                                <input type="text" class="form-control" name="senha">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Curso</label>
                                <input type="text" class="form-control" name="curso" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Departamento</label>
                                <input type="text" class="form-control" name="departamento" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Início</label>
                                <input type="date" class="form-control" name="data_inicio">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Fim </label>
                                <input type="date" class="form-control" name="data_fim">
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Cadastrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>