<?php
require_once 'includes/init.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/conexao.php';

$hoje = date('Y-m-d');
$mensagem = '';
$erro = '';

// Processar marcação de presença (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_presenca'])) {
    $estagiario_id = $_POST['estagiario_id'] ?? null;
    $status = $_POST['status'] ?? 'presente';
    $observacao = trim($_POST['observacao'] ?? '');

    if ($estagiario_id) {
        try {
            // Verifica se já existe presença hoje para este estagiário
            $stmt = query("SELECT id FROM presencas WHERE estagiario_id = ? AND data = ?", [$estagiario_id, $hoje]);
            if ($stmt->fetch()) {
                $erro = 'Presença já marcada hoje para este estagiário.';
            } else {
                // Insere nova presença
                query(
                    "INSERT INTO presencas (estagiario_id, data, hora_entrada, status, observacao) 
                     VALUES (?, ?, CURTIME(), ?, ?)",
                    [$estagiario_id, $hoje, $status, $observacao]
                );
                $mensagem = 'Presença marcada com sucesso!';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao marcar presença: ' . $e->getMessage();
        }
    } else {
        $erro = 'Selecione um estagiário.';
    }
}

// Buscar estagiários para o select
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

// Buscar presenças recentes (últimos 7 dias, para visão geral)
$presencas_recentes = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome, DATE_FORMAT(p.data, '%d/%m/%Y') AS data_formatada
        FROM presencas p
        INNER JOIN estagiarios e ON p.estagiario_id = e.id
        INNER JOIN usuarios u ON e.usuario_id = u.id
        WHERE p.data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY p.data DESC, u.nome
    ");
    $stmt->execute();
    $presencas_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao carregar histórico: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Presenças</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Controle de Estagiários</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="estagiarios.php">Estagiários</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Registro de Presenças - <?= date('d/m/Y') ?></h2>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Marcar Presença Hoje
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="marcar_presenca" value="1">

                    <div class="mb-3">
                        <label for="estagiario_id" class="form-label">Estagiário </label>
                        <select class="form-select" id="estagiario_id" name="estagiario_id" required>
                            
                            <?php foreach ($estagiarios as $est): ?>
                                <option value="<?= $est['id'] ?>"><?= htmlspecialchars($est['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status </label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="presente" value="presente" checked>
                                <label class="form-check-label" for="presente">Presente</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="ausente" value="ausente">
                                <label class="form-check-label" for="ausente">Ausente</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="justificado" value="justificado">
                                <label class="form-check-label" for="justificado">Justificado</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observacao" class="form-label">Observação (opcional)</label>
                        <textarea class="form-control" id="observacao" name="observacao" rows="2" placeholder="ex.: Atestado médico"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Marcar Presença</button>
                </form>
            </div>
        </div>

        
        <h3 class="mb-3">Histórico de Presenças (Últimos 7 Dias)</h3>

        <?php if (empty($presencas_recentes)): ?>
            <div class="alert alert-info">Nenhuma presença registrada nos últimos 7 dias.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Data</th>
                            <th>Estagiário</th>
                            <th>Status</th>
                            <th>Hora Entrada</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presencas_recentes as $p): ?>
                            <tr>
                                <td><?= $p['data_formatada'] ?></td>
                                <td><?= htmlspecialchars($p['nome']) ?></td>
                                <td>
                                    <?php
                                    $badge = match($p['status']) {
                                        'presente' => 'bg-success',
                                        'ausente' => 'bg-danger',
                                        'justificado' => 'bg-warning',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= ucfirst($p['status']) ?></span>
                                </td>
                                <td><?= $p['hora_entrada'] ?? '-' ?></td>
                                <td><?= htmlspecialchars($p['observacao'] ?? '-') ?></td>
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