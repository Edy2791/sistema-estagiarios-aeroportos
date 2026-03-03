<?php
require_once 'includes/init.php';


if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}



$mensagem = $erro = '';
$filtro_nome = trim($_GET['nome'] ?? '');
$filtro_inicio = $_GET['data_inicio'] ?? '';
$filtro_fim = $_GET['data_fim'] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    $id = (int)$_POST['id'];
    try {
        query("DELETE FROM estagiarios WHERE id = ?", [$id]);
        $mensagem = 'Estagiário removido com sucesso (presenças e tarefas associadas também).';
    } catch (PDOException $e) {
        $erro = 'Erro ao excluir: ' . $e->getMessage();
    }
}

// Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $curso = trim($_POST['curso']);
    $departamento = trim($_POST['departamento']);
    $inicio = $_POST['data_inicio'] ?: null;
    $fim = $_POST['data_fim'] ?: null;

    if ($id && $nome && $email) {
        try {
            query("UPDATE usuarios SET nome = ?, email = ? WHERE id = (SELECT usuario_id FROM estagiarios WHERE id = ?)", [$nome, $email, $id]);
            query("UPDATE estagiarios SET curso = ?, departamento = ?, data_inicio = ?, data_fim = ? WHERE id = ?", [$curso, $departamento, $inicio, $fim, $id]);
            $mensagem = 'Estagiário atualizado!';
        } catch (PDOException $e) {
            $erro = 'Erro ao editar: ' . $e->getMessage();
        }
    } else {
        $erro = 'Campos obrigatórios vazios.';
    }
}

// Lista com filtros
$sql = "SELECT e.id, u.nome, u.email, e.curso, e.departamento, e.data_inicio, e.data_fim
        FROM estagiarios e INNER JOIN usuarios u ON e.usuario_id = u.id WHERE 1=1";
$params = [];

if ($filtro_nome) {
    $sql .= " AND u.nome LIKE ?";
    $params[] = "%$filtro_nome%";
}
if ($filtro_inicio) {
    $sql .= " AND e.data_inicio >= ?";
    $params[] = $filtro_inicio;
}
if ($filtro_fim) {
    $sql .= " AND e.data_fim <= ?";
    $params[] = $filtro_fim;
}
$sql .= " ORDER BY u.nome ASC";

try {
    $stmt = query($sql, $params);
    $estagiarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao carregar lista: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estagiários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Controle de Estagiários</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="estagiarios.php">Estagiários</a></li>
                <li class="nav-item"><a class="nav-link" href="presencas.php">Presenças</a></li>
                <li class="nav-item"><a class="nav-link" href="tarefas.php">Tarefas</a></li>
                <li class="nav-item"><a class="nav-link" href="relatorios.php">Relatórios</a></li>
                <li class="nav-item"><a class="nav-link" href="perfil.php">Meu Perfil</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Lista de Estagiários</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Filtros</div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="nome" placeholder="Nome" value="<?= htmlspecialchars($filtro_nome) ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="data_inicio" value="<?= htmlspecialchars($filtro_inicio) ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars($filtro_fim) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($estagiarios)): ?>
        <div class="alert alert-info">Nenhum estagiário encontrado. <a href="cadastro.php">Cadastrar novo</a>.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Curso</th>
                        <th>Departamento</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estagiarios as $est): ?>
                        <tr>
                            <td><?= htmlspecialchars($est['nome']) ?></td>
                            <td><?= htmlspecialchars($est['email']) ?></td>
                            <td><?= htmlspecialchars($est['curso'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($est['departamento'] ?? '-') ?></td>
                            <td><?= $est['data_inicio'] ? date('d/m/Y', strtotime($est['data_inicio'])) : '-' ?></td>
                            <td><?= $est['data_fim'] ? date('d/m/Y', strtotime($est['data_fim'])) : '-' ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $est['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir <?= htmlspecialchars(addslashes($est['nome'])) ?>? Esta ação deleta presenças e tarefas associadas.');">
                                    <input type="hidden" name="id" value="<?= $est['id'] ?>">
                                    <input type="hidden" name="excluir" value="1">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $est['id'] ?>">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Editar <?= htmlspecialchars($est['nome']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $est['id'] ?>">
                                            <input type="hidden" name="editar" value="1">

                                            <div class="mb-3">
                                                <label class="form-label">Nome </label>
                                                <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($est['nome']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email </label>
                                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($est['email']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Curso</label>
                                                <input type="text" class="form-control" name="curso" value="<?= htmlspecialchars($est['curso'] ?? '') ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Departamento</label>
                                                <input type="text" class="form-control" name="departamento" value="<?= htmlspecialchars($est['departamento'] ?? '') ?>">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Início</label>
                                                    <input type="date" class="form-control" name="data_inicio" value="<?= $est['data_inicio'] ?? '' ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Fim</label>
                                                    <input type="date" class="form-control" name="data_fim" value="<?= $est['data_fim'] ?? '' ?>">
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Salvar</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted">Total: <?= count($estagiarios) ?></p>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="cadastro.php" class="btn btn-success">+ Novo Estagiário</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>