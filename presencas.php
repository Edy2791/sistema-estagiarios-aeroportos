<?php
require_once 'includes/init.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}

$hoje = date('Y-m-d');
$mensagem = $erro = '';

// 1. Processar Registo de Presença
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_presenca'])) {
    $estagiario_id = (int)$_POST['estagiario_id'];
    $status = $_POST['status'];
    $obs = trim($_POST['observacao']);

    try {
        // Verifica se já existe registo hoje
        $check = $pdo->prepare("SELECT id FROM presencas WHERE estagiario_id = ? AND data = ?");
        $check->execute([$estagiario_id, $hoje]);
        
        if ($check->fetch()) {
            $erro = "Já foi registada a presença deste estagiário hoje.";
        } else {
            $sql = "INSERT INTO presencas (estagiario_id, data, hora_entrada, status, observacao) VALUES (?, ?, CURTIME(), ?, ?)";
            $pdo->prepare($sql)->execute([$estagiario_id, $hoje, $status, $obs]);
            $mensagem = "Presença registada com sucesso!";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao gravar: " . $e->getMessage();
    }
}

// 2. Carregar Estagiários para o Select
$estagiarios_lista = $pdo->query("SELECT id, nome FROM estagiarios ORDER BY nome")->fetchAll();

// 3. Carregar Histórico de Hoje
$sql_hoje = "SELECT p.*, e.nome as estagiario_nome 
             FROM presencas p 
             JOIN estagiarios e ON p.estagiario_id = e.id 
             WHERE p.data = ? 
             ORDER BY p.hora_entrada DESC";
$stmt_hoje = $pdo->prepare($sql_hoje);
$stmt_hoje->execute([$hoje]);
$presencas_dia = $stmt_hoje->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>ADM - Presenças</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold mb-3">Marcar Presença</h4>
                <p class="text-muted small">Data: <?= date('d/m/Y') ?></p>

                <?php if($erro): ?> <div class="alert alert-danger small"><?= $erro ?></div> <?php endif; ?>
                <?php if($mensagem): ?> <div class="alert alert-success small"><?= $mensagem ?></div> <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Estagiário</label>
                        <select name="estagiario_id" class="form-select" required>
                            <option value="">-- Selecionar --</option>
                            <?php foreach ($estagiarios_lista as $est): ?>
                                <option value="<?= $est['id'] ?>"><?= htmlspecialchars($est['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="status" class="form-select">
                            <option value="presente">Presente</option>
                            <option value="ausente">Ausente</option>
                            <option value="justificado">Justificado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Observação</label>
                        <textarea name="observacao" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" name="marcar_presenca" class="btn btn-primary w-100">Confirmar Registo</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold mb-3">Presenças de Hoje</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Estagiário</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($presencas_dia)): ?>
                                <tr><td colspan="3" class="text-center text-muted">Nenhum registo até agora.</td></tr>
                            <?php else: foreach ($presencas_dia as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['estagiario_nome']) ?></td>
                                    <td><?= $p['hora_entrada'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $p['status'] == 'presente' ? 'success' : ($p['status'] == 'ausente' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>