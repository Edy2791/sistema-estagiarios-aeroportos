<?php
require_once 'includes/init.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}

$mensagem = $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'])) {
    $id = (int)$_POST['id'];
    try {
        $pdo->prepare("DELETE FROM estagiarios WHERE id = ?")->execute([$id]);
        $mensagem = 'Registo removido com sucesso.';
    } catch (PDOException $e) {
        $erro = 'Erro ao eliminar: ' . $e->getMessage();
    }
}


$nome = $_GET['nome'] ?? '';
$escola = $_GET['escola'] ?? '';
$ano = $_GET['ano'] ?? '';

$sql = "SELECT e.*, d.nome as departamento 
        FROM estagiarios e 
        JOIN departamentos d ON e.departamento_id = d.id 
        WHERE 1=1";

$params = [];
if ($nome) { $sql .= " AND e.nome LIKE ?"; $params[] = "%$nome%"; }
if ($escola) { $sql .= " AND e.instituicao_ensino LIKE ?"; $params[] = "%$escola%"; }
if ($ano) { $sql .= " AND e.ano_estagio = ?"; $params[] = $ano; }

$sql .= " ORDER BY e.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$estagiarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADM - Gestão de Estagiários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --adm-blue: #003366; --bg-light: #f4f7f9; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); }
        .navbar-adm { background: white; border-bottom: 1px solid #e2e8f0; }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-adm { background-color: var(--adm-blue); color: white; }
        .btn-adm:hover { background-color: #002244; color: white; }
        .badge-status { font-size: 0.75rem; padding: 5px 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-adm sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php" style="color: var(--adm-blue);">
         <img src="assets\aeroportos.jpg" height="80"> Aeroportos de Moçambique
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold" href="estagiarios.php">Estagiários</a></li>
                <li class="nav-item"><a class="nav-link" href="presencas.php">Presenças</a></li>
                <li class="nav-item"><a class="nav-link" href="departamentos.php">Departamentos</a></li>
            </ul>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Sair</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0">Lista de Estagiários</h3>
           
        </div>
        <a href="cadastro.php" class="btn btn-adm shadow-sm"><i class="bi bi-plus-lg me-2"></i>Novo Registo</a>
    </div>

    <div class="card border-0 shadow-sm p-3 mb-4">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="nome" class="form-control" placeholder="Pesquisar por nome" value="<?= htmlspecialchars($nome) ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="escola" class="form-control" placeholder="Escola/Universidade" value="<?= htmlspecialchars($escola) ?>">
            </div>
            <div class="col-md-2">
                <input type="number" name="ano" class="form-control" placeholder="Ano" value="<?= htmlspecialchars($ano) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-dark w-100">Aplicar Filtros</button>
            </div>
        </form>
    </div>

    <?php if($mensagem): ?> <div class="alert alert-success border-0 shadow-sm"><?= $mensagem ?></div> <?php endif; ?>
    <?php if($erro): ?> <div class="alert alert-danger border-0 shadow-sm"><?= $erro ?></div> <?php endif; ?>

    <div class="table-container p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nome / E-mail</th>
                        <th>Departamento</th>
                        <th>Instituição</th>
                        <th>Ano (Sexo)</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($estagiarios)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum registo encontrado.</td></tr>
                    <?php else: foreach ($estagiarios as $e): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($e['nome']) ?></div>
                            <div class="text-muted x-small" style="font-size: 0.8rem;"><?= htmlspecialchars($e['email']) ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($e['departamento']) ?></span></td>
                        <td><?= htmlspecialchars($e['instituicao_ensino'] ?: 'Não informada') ?></td>
                        <td><?= $e['ano_estagio'] ?> (<?= $e['sexo'] ?>)</td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="editar_estagiario.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                
                                <form method="POST" onsubmit="return confirm('Deseja eliminar este registo histórico?');" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button name="excluir" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3 p-2 bg-light border-top rounded text-end">
            <small class="text-muted">Total de registos: <strong><?= count($estagiarios) ?></strong></small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>