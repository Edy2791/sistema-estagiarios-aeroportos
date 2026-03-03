<?php
require_once 'includes/init.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$nome = $_SESSION['nome'] ?? 'Usuário';
$tipo = $_SESSION['tipo'] ?? 'estagiario';




$total_estagiarios = 0;
$tarefas_pendentes = 0;
$presencas_hoje = 0;

if ($tipo === 'admin' || $tipo === 'supervisor') {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM estagiarios");
        $total_estagiarios = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM tarefas WHERE status != 'concluida'");
        $tarefas_pendentes = $stmt->fetch()['total'];

        $hoje = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM presencas WHERE data = ? AND status = 'presente'");
        $stmt->execute([$hoje]);
        $presencas_hoje = $stmt->fetch()['total'];
    } catch (PDOException $e) {
        error_log("Erro dashboard admin: " . $e->getMessage());
    }
}

// Dados para estagiário
$minhas_presencas = [];
$minhas_tarefas = [];

if ($tipo === 'estagiario') {
    $meu_id = $_SESSION['usuario_id'];

    $stmt = query("SELECT id FROM estagiarios WHERE usuario_id = ?", [$meu_id]);
    $meu_estagiario = $stmt->fetch();
    $estagiario_id = $meu_estagiario ? $meu_estagiario['id'] : null;

    if ($estagiario_id) {
        // Presenças recentes (30 dias)
        $stmt = query("
            SELECT *, DATE_FORMAT(data, '%d/%m/%Y') AS data_formatada 
            FROM presencas 
            WHERE estagiario_id = ? 
            AND data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY data DESC
        ", [$estagiario_id]);
        $minhas_presencas = $stmt->fetchAll();

        // Tarefas
        $stmt = query("
            SELECT *, DATE_FORMAT(prazo, '%d/%m/%Y') AS prazo_formatado 
            FROM tarefas 
            WHERE estagiario_id = ? 
            ORDER BY prazo ASC
        ", [$estagiario_id]);
        $minhas_tarefas = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estagiários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card:hover { transform: scale(1.02); transition: 0.2s; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
        <img src="assets\aeroportos.jpg" alt="Aeroportos de Maputo" height="80" width="80" class="me-2"> 
            <a class="navbar-brand" href="dashboard.php">Controle de Estagiários</a>
            
           
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    
                    <li class="nav-item"><a class="nav-link" href="perfil.php">Meu Perfil</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair </a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Bem-vindo, <?= htmlspecialchars($nome) ?>!</h1>
        <p class="lead">Tipo: <strong><?= ucfirst($tipo) ?></strong></p>

        <?php if ($tipo === 'admin' || $tipo === 'supervisor'): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5>Estagiários Cadastrados</h5>
                            <p class="display-4"><?= $total_estagiarios ?></p>
                            <a href="estagiarios.php" class="btn btn-light">Ver Lista</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5>Tarefas Pendentes</h5>
                            <p class="display-4"><?= $tarefas_pendentes ?></p>
                            <a href="tarefas.php" class="btn btn-light">Gerenciar</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5>Presenças Hoje (<?= date('d/m/Y') ?>)</h5>
                            <p class="display-4"><?= $presencas_hoje ?></p>
                            <a href="presencas.php" class="btn btn-light">Registrar</a>
                           
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="alert alert-success">
                        <h4>Olá, estagiário!</h4>
                        <p>Aqui tens o teu resumo pessoal de presenças e tarefas nos Aeroportos de Maputo.</p>
                    </div>
                </div>

                <!-- Minhas Presenças -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            Minhas Presenças (últimos 30 dias)
                        </div>
                        <div class="card-body">
                            <?php if (empty($minhas_presencas)): ?>
                                <p class="text-muted">Ainda não tens presenças registradas.</p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($minhas_presencas as $p): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= $p['data_formatada'] ?>
                                            <span class="badge <?= $p['status'] === 'presente' ? 'bg-success' : ($p['status'] === 'ausente' ? 'bg-danger' : 'bg-warning') ?>">
                                                <?= ucfirst($p['status']) ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Minhas Tarefas (com botão para concluir) -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            Minhas Tarefas Atribuídas
                        </div>
                        <div class="card-body">
                            <?php if (empty($minhas_tarefas)): ?>
                                <p class="text-muted">Nenhuma tarefa atribuída no momento.</p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($minhas_tarefas as $t): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                            <div class="me-3 mb-2 mb-md-0">
                                                <strong><?= htmlspecialchars($t['descricao']) ?></strong><br>
                                                <small>
                                                    Prazo: <?= $t['prazo_formatado'] ?? 'Sem prazo' ?> | 
                                                    Status: <span class="badge bg-<?= $t['status'] === 'concluida' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($t['status']) ?>
                                                    </span>
                                                </small>
                                            </div>
                                            <?php if ($t['status'] !== 'concluida'): ?>
                                                <form method="POST" action="tarefas.php" class="mt-2 mt-md-0">
                                                    <input type="hidden" name="tarefa_id" value="<?= $t['id'] ?>">
                                                    <input type="hidden" name="concluir_tarefa" value="1">
                                                    <button type="submit" class="btn btn-sm btn-success">Marcar Concluída</button>
                                                </form>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <footer class="mt-5 text-center text-muted">
            <p>© <?= date('Y') ?> - Sistema de Controle de Estagiários | Aeroportos de Maputo</p>
            <a href="relatorios.php" class="btn btn-outline-primary mb-3">Ver Relatórios</a>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>