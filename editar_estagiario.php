<?php
require_once 'includes/init.php';

// Segurança: Apenas Admin e Supervisor editam
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}

$erro = $sucesso = '';
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: estagiarios.php');
    exit;
}

// 1. Carregar dados atuais do estagiário
try {
    $stmt = $pdo->prepare("SELECT * FROM estagiarios WHERE id = ?");
    $stmt->execute([$id]);
    $estagiario = $stmt->fetch();

    if (!$estagiario) {
        die("Estagiário não encontrado.");
    }

    // Carregar departamentos para o select
    $stmt_dep = $pdo->query("SELECT id, nome FROM departamentos ORDER BY nome");
    $departamentos = $stmt_dep->fetchAll();
} catch (PDOException $e) {
    $erro = "Erro de base de dados: " . $e->getMessage();
}

// 2. Processar a Atualização (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $dep_id = (int)$_POST['departamento_id'];
    $escola = trim($_POST['instituicao_ensino']);
    $sexo = $_POST['sexo'];
    $ano = $_POST['ano_estagio'];
    $nivel = $_POST['nivel_academico'];
    $bi = trim($_POST['bi_nr']);
    $emergencia = trim($_POST['contacto_emergencia']);

    if ($nome && $email) {
        try {
            $sql = "UPDATE estagiarios SET 
                    nome = ?, email = ?, departamento_id = ?, instituicao_ensino = ?, 
                    sexo = ?, ano_estagio = ?, nivel_academico = ?, bi_nr = ?, 
                    contacto_emergencia = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $dep_id, $escola, $sexo, $ano, $nivel, $bi, $emergencia, $id]);
            
            $sucesso = "Dados atualizados com sucesso!";
            // Recarregar dados atualizados
            $stmt = $pdo->prepare("SELECT * FROM estagiarios WHERE id = ?");
            $stmt->execute([$id]);
            $estagiario = $stmt->fetch();
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar: " . $e->getMessage();
        }
    } else {
        $erro = "Nome e E-mail são obrigatórios.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>ADM - Editar Estagiário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">ADM CONTROLE</a>
        <a href="estagiarios.php" class="btn btn-sm btn-outline-light">Voltar à Lista</a>
    </div>
</nav>

<div class="container pb-5">
    <div class="card shadow-sm border-0 p-4">
        <h3 class="fw-bold mb-4">Editar Perfil do Estagiário</h3>

        <?php if($erro): ?> <div class="alert alert-danger"><?= $erro ?></div> <?php endif; ?>
        <?php if($sucesso): ?> <div class="alert alert-success"><?= $sucesso ?></div> <?php endif; ?>

        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Nome Completo</label>
                <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($estagiario['nome']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">E-mail</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($estagiario['email']) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Departamento</label>
                <select name="departamento_id" class="form-select">
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $d['id'] == $estagiario['departamento_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Sexo</label>
                <select name="sexo" class="form-select">
                    <option value="M" <?= $estagiario['sexo'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                    <option value="F" <?= $estagiario['sexo'] == 'F' ? 'selected' : '' ?>>Feminino</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">BI Nº</label>
                <input type="text" name="bi_nr" class="form-control" value="<?= htmlspecialchars($estagiario['bi_nr']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Instituição de Ensino</label>
                <input type="text" name="instituicao_ensino" class="form-control" value="<?= htmlspecialchars($estagiario['instituicao_ensino']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Ano do Estágio</label>
                <input type="number" name="ano_estagio" class="form-control" value="<?= $estagiario['ano_estagio'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Nível Académico</label>
                <select name="nivel_academico" class="form-select">
                    <option value="Licenciatura" <?= $estagiario['nivel_academico'] == 'Licenciatura' ? 'selected' : '' ?>>Licenciatura</option>
                    <option value="Técnico Médio" <?= $estagiario['nivel_academico'] == 'Técnico Médio' ? 'selected' : '' ?>>Técnico Médio</option>
                    <option value="Mestrado" <?= $estagiario['nivel_academico'] == 'Mestrado' ? 'selected' : '' ?>>Mestrado</option>
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-bold">Contacto de Emergência</label>
                <input type="text" name="contacto_emergencia" class="form-control" value="<?= htmlspecialchars($estagiario['contacto_emergencia']) ?>">
            </div>

            <div class="col-12 mt-4 text-end">
                <button type="submit" class="btn btn-primary px-5">Guardar Alterações</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>