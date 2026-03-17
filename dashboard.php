<?php
require_once 'includes/init.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$nome_user = $_SESSION['nome'] ?? 'Utilizador';
$tipo = $_SESSION['tipo'] ?? 'estagiario';

$total_estagiarios = 0;
$presencas_hoje = 0;
$estagiarios_por_dep = [];

if ($tipo === 'admin' || $tipo === 'supervisor') {
    try {
        // 1. Total de estagiários no arquivo
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM estagiarios");
        $total_estagiarios = $stmt->fetch()['total'];

        // 2. Presenças registadas hoje
        $hoje = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM presencas WHERE data = ? AND status = 'presente'");
        $stmt->execute([$hoje]);
        $presencas_hoje = $stmt->fetch()['total'];

        // 3. Distribuição por Departamento (A pedido da Engenharia)
        $sql_dep = "SELECT d.nome as departamento, COUNT(e.id) as total 
                    FROM departamentos d 
                    LEFT JOIN estagiarios e ON d.id = e.departamento_id 
                    GROUP BY d.id, d.nome 
                    ORDER BY total DESC";
        $estagiarios_por_dep = $pdo->query($sql_dep)->fetchAll();

    } catch (PDOException $e) {
        error_log("Erro no dashboard: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>ADM - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --adm-blue: #003366; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; }
        .card-stat { border: none; border-radius: 12px; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        .icon-box { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold">Bem-vindo, <?= htmlspecialchars($nome_user) ?></h2>
        <p class="text-muted">Painel de Controlo dos Aeroportos de Moçambique</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-stat shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary text-white me-3"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="text-muted small">Total de Estagiários</div>
                        <h3 class="fw-bold m-0"><?= $total_estagiarios ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success text-white me-3"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="text-muted small">Presentes Hoje</div>
                        <h3 class="fw-bold m-0"><?= $presencas_hoje ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3">Estagiários por Departamento</div>
                <div class="card-body">
                    <?php if (empty($estagiarios_por_dep)): ?>
                        <p class="text-muted">Sem dados disponíveis.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <tbody>
                                    <?php foreach ($estagiarios_por_dep as $dep): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($dep['departamento']) ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-dark rounded-pill"><?= $dep['total'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3">Ações Rápidas</div>
                <div class="card-body d-grid gap-2">
                    <a href="cadastro.php" class="btn btn-outline-primary p-3 text-start">
                        <i class="bi bi-person-plus me-2"></i> Cadastrar Novo Estagiário
                    </a>
                    <a href="presencas.php" class="btn btn-outline-dark p-3 text-start">
                        <i class="bi bi-check2-square me-2"></i> Registar Presenças 
                    </a>
                    <a href="estagiarios.php" class="btn btn-outline-secondary p-3 text-start">
                        <i class="bi bi-search me-2"></i> Pesquisar 
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>