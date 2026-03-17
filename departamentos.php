<?php
// departamentos.php – Registro e lista de departamentos (apenas admin)

require_once 'includes/conexao.php';
require_once 'includes/session_handler.php';

$handler = new DbSessionHandler($pdo);
session_set_save_handler($handler, true);
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$erro = $mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao'] ?? '');

    if ($nome) {
        try {
            query("INSERT INTO departamentos (nome, descricao) VALUES (?, ?)", [$nome, $descricao]);
            $mensagem = 'Departamento cadastrado!';
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    } else {
        $erro = 'Nome obrigatório.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao'] ?? '');

    if ($id && $nome) {
        try {
            query("UPDATE departamentos SET nome = ?, descricao = ? WHERE id = ?", [$nome, $descricao, $id]);
            $mensagem = 'Departamento atualizado!';
        } catch (PDOException $e) {
            $erro = 'Erro ao editar: ' . $e->getMessage();
        }
    } else {
        $erro = 'Nome obrigatório.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    $id = (int)$_POST['id'];
    try {
        query("DELETE FROM departamentos WHERE id = ?", [$id]);
        $mensagem = 'Departamento excluído!';
    } catch (PDOException $e) {
        $erro = 'Erro ao excluir: ' . $e->getMessage();
    }
}

// Lista de departamentos
$departamentos = [];
try {
    $stmt = $pdo->query("SELECT * FROM departamentos ORDER BY nome");
    $departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao carregar departamentos: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departamentos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --adm-blue: #003366; /* Azul Aeroportos */
        --adm-accent: #0056b3;
        --bg-light: #f4f7f9;
    }

    body { 
        font-family: 'Inter', sans-serif; 
        background-color: var(--bg-light); 
        color: #334155;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .btn-primary {
        background-color: var(--adm-blue);
        border: none;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background-color: var(--adm-accent);
        transform: translateY(-1px);
    }

    .sidebar-nav {
        background: white;
        border-right: 1px solid #e2e8f0;
        height: 100vh;
    }

    .nav-link {
        color: #64748b;
        padding: 12px 20px;
        border-radius: 8px;
        margin: 4px 10px;
    }

    .nav-link.active {
        background-color: #f1f5f9;
        color: var(--adm-blue);
        font-weight: 600;
    }
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <img src="assets\aeroportos.jpg" height="80">
        <a class="navbar-brand" href="dashboard.php">Controle de Estagiários</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="estagiarios.php">Estagiários</a></li>
                <li class="nav-item"><a class="nav-link active" href="departamentos.php">Departamentos</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Gestão de Departamentos</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <!-- Cadastro de novo departamento -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Cadastrar Novo Departamento</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="cadastrar" value="1">
                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" class="form-control" name="nome" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descrição (opcional)</label>
                    <textarea class="form-control" name="descricao" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </form>
        </div>
    </div>

    <!-- Lista de departamentos -->
    <h3>Lista de Departamentos</h3>
    <?php if (empty($departamentos)): ?>
        <div class="alert alert-info">Nenhum departamento cadastrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departamentos as $dep): ?>
                        <tr>
                            <td><?= htmlspecialchars($dep['nome']) ?></td>
                            <td><?= htmlspecialchars($dep['descricao'] ?? '-') ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $dep['id'] ?>">
                                    Editar
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir <?= htmlspecialchars($dep['nome']) ?>?');">
                                    <input type="hidden" name="id" value="<?= $dep['id'] ?>">
                                    <input type="hidden" name="excluir" value="1">
                                    <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        <!-- Modal Edição -->
                        <div class="modal fade" id="editModal<?= $dep['id'] ?>">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Editar <?= htmlspecialchars($dep['nome']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $dep['id'] ?>">
                                            <input type="hidden" name="editar" value="1">
                                            <div class="mb-3">
                                                <label class="form-label">Nome *</label>
                                                <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($dep['nome']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Descrição</label>
                                                <textarea class="form-control" name="descricao" rows="2"><?= htmlspecialchars($dep['descricao'] ?? '') ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Salvar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>