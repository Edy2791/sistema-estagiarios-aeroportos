<?php


require_once 'includes/init.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/conexao.php';

$mensagem = '';
$erro = '';
$filtro_estagiario = $_GET['estagiario_id'] ?? '';

$estagiarios = [];
try {
    $stmt = $pdo->query("
        SELECT e.id, u.nome 
        FROM estagiarios e 
        INNER JOIN usuarios u ON e.usuario_id = u.id 
        ORDER BY u.nome
    ");
    $estagiarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao carregar estagiários: ' . $e->getMessage();
}


$tem_criado_em = false;
try {
    $pdo->query("SELECT criado_em FROM tarefas LIMIT 1");
    $tem_criado_em = true;
} catch (PDOException $e) {
   
}


$presencas = [];
$total_presente = $total_ausente = $total_justificado = 0;
try {
    $sql = "
        SELECT p.*, u.nome AS estagiario_nome, DATE_FORMAT(p.data, '%d/%m/%Y') AS data_formatada
        FROM presencas p
        INNER JOIN estagiarios e ON p.estagiario_id = e.id
        INNER JOIN usuarios u ON e.usuario_id = u.id
        WHERE MONTH(p.data) = MONTH(CURDATE()) AND YEAR(p.data) = YEAR(CURDATE())
    ";
    $params = [];

    if ($filtro_estagiario) {
        $sql .= " AND e.id = ?";
        $params[] = $filtro_estagiario;
    }
    $sql .= " ORDER BY p.data DESC";

    $stmt = query($sql, $params);
    $presencas = $stmt->fetchAll();

    foreach ($presencas as $p) {
        if ($p['status'] === 'presente') $total_presente++;
        elseif ($p['status'] === 'ausente') $total_ausente++;
        elseif ($p['status'] === 'justificado') $total_justificado++;
    }
} catch (PDOException $e) {
    $erro = 'Erro ao gerar relatório de presenças: ' . $e->getMessage();
}


$tarefas = [];
$total_concluidas = $total_pendentes = 0;
try {
    $sql = "
        SELECT t.*, u.nome AS estagiario_nome, DATE_FORMAT(t.prazo, '%d/%m/%Y') AS prazo_formatado,
               DATE_FORMAT(t.data_conclusao, '%d/%m/%Y %H:%i') AS concluida_em
        FROM tarefas t
        INNER JOIN estagiarios e ON t.estagiario_id = e.id
        INNER JOIN usuarios u ON e.usuario_id = u.id
    ";
    $params = [];

    if ($tem_criado_em) {
        $sql .= " WHERE MONTH(t.criado_em) = MONTH(CURDATE()) AND YEAR(t.criado_em) = YEAR(CURDATE())";
    } else {
        $sql .= " WHERE 1=1"; 
    }

    if ($filtro_estagiario) {
        $sql .= ($tem_criado_em ? " AND" : " AND") . " e.id = ?";
        $params[] = $filtro_estagiario;
    }

    $sql .= " ORDER BY t.status, t.prazo ASC";

    $stmt = query($sql, $params);
    $tarefas = $stmt->fetchAll();

    foreach ($tarefas as $t) {
        if ($t['status'] === 'concluida') $total_concluidas++;
        else $total_pendentes++;
    }
} catch (PDOException $e) {
    $erro = 'Erro ao gerar relatório de tarefas: ' . $e->getMessage();
}

// Exportar para CSV (se solicitado)
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $filename = $export_type . '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para acentos no Excel

    if ($export_type === 'presencas') {
        fputcsv($output, ['Data', 'Estagiário', 'Status', 'Observação']);

        foreach ($presencas as $p) {
            fputcsv($output, [
                $p['data_formatada'],
                $p['estagiario_nome'],
                ucfirst($p['status']),
                $p['observacao'] ?? ''
            ]);
        }
    } elseif ($export_type === 'tarefas') {
        fputcsv($output, ['Estagiário', 'Descrição', 'Prazo', 'Status', 'Concluída em']);

        foreach ($tarefas as $t) {
            fputcsv($output, [
                $t['estagiario_nome'],
                $t['descricao'],
                $t['prazo_formatado'] ?? '',
                ucfirst($t['status']),
                $t['concluida_em'] ?? ''
            ]);
        }
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Controle de Estagiários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                
                Controle de Estagiários
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="estagiarios.php">Estagiários</a></li>
                    <li class="nav-item"><a class="nav-link" href="presencas.php">Presenças</a></li>
                    <li class="nav-item"><a class="nav-link" href="tarefas.php">Tarefas</a></li>
                    <li class="nav-item"><a class="nav-link active" href="relatorios.php">Relatórios</a></li>
                    <li class="nav-item"><a class="nav-link" href="perfil.php">Meu Perfil</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Relatórios</h2>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <!-- Filtro -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">Filtrar por Estagiário (opcional)</div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="estagiario_id" class="form-label">Estagiário</label>
                        <select class="form-select" id="estagiario_id" name="estagiario_id">
                            <option value="">-- Todos --</option>
                            <?php foreach ($estagiarios as $est): ?>
                                <option value="<?= $est['id'] ?>" <?= $filtro_estagiario == $est['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($est['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Presenças -->
        <h4>Presenças no Mês Atual (<?= date('m/Y') ?>)</h4>
        <div class="row mb-4">
            <div class="col-md-4"><div class="card text-white bg-success"><div class="card-body"><h5>Presentes</h5><p class="display-6"><?= $total_presente ?></p></div></div></div>
            <div class="col-md-4"><div class="card text-white bg-danger"><div class="card-body"><h5>Ausentes</h5><p class="display-6"><?= $total_ausente ?></p></div></div></div>
            <div class="col-md-4"><div class="card text-white bg-warning"><div class="card-body"><h5>Justificados</h5><p class="display-6"><?= $total_justificado ?></p></div></div></div>
        </div>

        <?php if (!empty($presencas)): ?>
            <div class="text-end mb-3">
                <a href="relatorios.php?export=presencas<?= $filtro_estagiario ? '&estagiario_id=' . $filtro_estagiario : '' ?>" class="btn btn-outline-success">
                    <i class="bi bi-download"></i> Exportar Presenças (CSV)
                </a>
            </div>
        <?php endif; ?>

        <?php if (empty($presencas)): ?>
            <div class="alert alert-info">Nenhuma presença registrada no mês atual.</div>
        <?php else: ?>
            <div class="table-responsive mb-5">
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>Data</th><th>Estagiário</th><th>Status</th><th>Observação</th></tr></thead>
                    <tbody>
                        <?php foreach ($presencas as $p): ?>
                            <tr>
                                <td><?= $p['data_formatada'] ?></td>
                                <td><?= htmlspecialchars($p['estagiario_nome']) ?></td>
                                <td><span class="badge bg-<?= $p['status'] === 'presente' ? 'success' : ($p['status'] === 'ausente' ? 'danger' : 'warning') ?>"><?= ucfirst($p['status']) ?></span></td>
                                <td><?= htmlspecialchars($p['observacao'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Tarefas -->
        <h4>Tarefas no Mês Atual (<?= date('m/Y') ?>)</h4>
        <div class="row mb-4">
            <div class="col-md-6"><div class="card text-white bg-success"><div class="card-body"><h5>Concluídas</h5><p class="display-6"><?= $total_concluidas ?></p></div></div></div>
            <div class="col-md-6"><div class="card text-white bg-warning"><div class="card-body"><h5>Pendentes/Em Andamento</h5><p class="display-6"><?= $total_pendentes ?></p></div></div></div>
        </div>

        <?php if (!empty($tarefas)): ?>
            <div class="text-end mb-3">
                <a href="relatorios.php?export=tarefas<?= $filtro_estagiario ? '&estagiario_id=' . $filtro_estagiario : '' ?>" class="btn btn-outline-success">
                    <i class="bi bi-download"></i> Exportar Tarefas (CSV)
                </a>
            </div>
        <?php endif; ?>

        <?php if (empty($tarefas)): ?>
            <div class="alert alert-info">Nenhuma tarefa registrada no mês atual.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark"><tr><th>Estagiário</th><th>Descrição</th><th>Prazo</th><th>Status</th><th>Concluída em</th></tr></thead>
                    <tbody>
                        <?php foreach ($tarefas as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['estagiario_nome']) ?></td>
                                <td><?= htmlspecialchars($t['descricao']) ?></td>
                                <td><?= $t['prazo_formatado'] ?? '-' ?></td>
                                <td><span class="badge bg-<?= $t['status'] === 'concluida' ? 'success' : 'warning' ?>"><?= ucfirst($t['status']) ?></span></td>
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