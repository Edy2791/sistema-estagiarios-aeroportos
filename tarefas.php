<?php
// tarefas.php

session_start();

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/conexao.php';

$mensagem = '';
$erro = '';

// Processar atribuição de tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atribuir_tarefa'])) {
    $estagiario_id = $_POST['estagiario_id'] ?? null;
    $descricao = trim($_POST['descricao'] ?? '');
    $prazo = $_POST['prazo'] ?? null;

    if ($estagiario_id && !empty($descricao)) {
        try {
            query(
                "INSERT INTO tarefas (estagiario_id, descricao, prazo, status, criado_por) 
                 VALUES (?, ?, ?, 'pendente', ?)",
                [$estagiario_id, $descricao, $prazo ?: null, $_SESSION['usuario_id']]
            );
            $mensagem = 'Tarefa atribuída com sucesso!';
        } catch (PDOException $e) {
            $erro = 'Erro ao atribuir tarefa: ' . $e->getMessage();
        }
    } else {
        $erro = 'Selecione estagiário e preencha a descrição.';
    }
}

// Processar marcação como concluída (supervisor ou estagiário)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_tarefa'])) {
    $tarefa_id = $_POST['tarefa_id'] ?? null;
    if ($tarefa_id) {
        try {
            query(
                "UPDATE tarefas SET status = 'concluida', data_conclusao = NOW() WHERE id = ?",
                [$tarefa_id]
            );
            $mensagem = 'Tarefa marcada como concluída!';
        } catch (PDOException $e) {
            $erro = 'Erro ao concluir tarefa: ' . $e->getMessage();
        }
    }
}

// Buscar estagiários
$estagiarios = [];
try {
    $stmt = $pdo->query("
        SELECT e.id, u.nome 
        FROM estagiarios e 
        INNER JOIN usuarios u ON e.usuario_id = u.id 
        WHERE u.tipo = 'estagiario' 
        ORDER BY u.nome
    ");
    $estagiarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao carregar estagiários: ' . $e->getMessage();
}

// Tarefas pendentes / em andamento
$tarefas_pendentes = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nome AS estagiario_nome, DATE_FORMAT(t.prazo, '%d/%m/%Y') AS prazo_formatado
        FROM tarefas t
        INNER JOIN estagiarios e ON t.estagiario_id = e.id
        INNER JOIN usuarios u ON e.usuario_id = u.id
        WHERE t.status IN ('pendente', 'em_andamento')
        ORDER BY t.prazo ASC, u.nome
    ");
    $stmt->execute();
    $tarefas_pendentes = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao carregar tarefas: ' . $e->getMessage();
}

// Tarefas concluídas recentes (últimos 7 dias)
$tarefas_concluidas = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nome AS estagiario_nome, DATE_FORMAT(t.prazo, '%d/%m/%Y') AS prazo_formatado,
               DATE_FORMAT(t.data_conclusao, '%d/%m/%Y %H:%i') AS concluida_em
        FROM tarefas t
        INNER JOIN estagiarios e ON t.estagiario_id = e.id
        INNER JOIN usuarios u ON e.usuario_id = u.id
        WHERE t.status = 'concluida'
          AND t.data_conclusao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY t.data_conclusao DESC
    ");
    $stmt->execute();
    $tarefas_concluidas = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao carregar concluídas: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Tarefas - Controle de Estagiários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Controle de Estagiários</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="estagiarios.php">Estagiários</a></li>
                    <li class="nav-item"><a class="nav-link" href="presencas.php">Presenças</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Gestão de Tarefas</h2>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <!-- Formulário para atribuir tarefa (só supervisor) -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Atribuir Nova Tarefa
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="atribuir_tarefa" value="1">
                    <div class="mb-3">
                        <label for="estagiario_id" class="form-label">Estagiário *</label>
                        <select class="form-select" id="estagiario_id" name="estagiario_id" required>
                            <option value="">-- Selecione --</option>
                            <?php foreach ($estagiarios as $est): ?>
                                <option value="<?= $est['id'] ?>"><?= htmlspecialchars($est['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição da Tarefa *</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="prazo" class="form-label">Prazo (opcional)</label>
                        <input type="date" class="form-control" id="prazo" name="prazo">
                    </div>
                    <button type="submit" class="btn btn-success">Atribuir Tarefa</button>
                </form>
            </div>
        </div>

        <!-- Tarefas Pendentes -->
        <h3 class="mb-3">Tarefas Pendentes / Em Andamento</h3>
        <?php if (empty($tarefas_pendentes)): ?>
            <div class="alert alert-info">Nenhuma tarefa pendente no momento.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Estagiário</th>
                            <th>Descrição</th>
                            <th>Prazo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tarefas_pendentes as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['estagiario_nome']) ?></td>
                                <td><?= htmlspecialchars($t['descricao']) ?></td>
                                <td><?= $t['prazo_formatado'] ?? '-' ?></td>
                                <td><span class="badge bg-warning"><?= ucfirst($t['status']) ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="tarefa_id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="concluir_tarefa" value="1">
                                        <button type="submit" class="btn btn-sm btn-success">Marcar Concluída</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Tarefas Concluídas Recentemente -->
        <h3 class="mb-4 mt-5">Tarefas Concluídas (últimos 7 dias)</h3>
        <?php if (empty($tarefas_concluidas)): ?>
            <div class="alert alert-info">Nenhuma tarefa concluída nos últimos 7 dias.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>Estagiário</th>
                            <th>Descrição</th>
                            <th>Prazo</th>
                            <th>Concluída em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tarefas_concluidas as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['estagiario_nome']) ?></td>
                                <td><?= htmlspecialchars($t['descricao']) ?></td>
                                <td><?= $t['prazo_formatado'] ?? '-' ?></td>
                                <td><?= $t['concluida_em'] ?? '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>