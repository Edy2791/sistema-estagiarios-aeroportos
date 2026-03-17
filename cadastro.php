<?php
require_once 'includes/init.php';

// Segurança: Apenas Admin e Supervisor cadastram
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'supervisor'])) {
    header('Location: index.php');
    exit;
}

$erro = $sucesso = '';

// Carregar departamentos para o dropdown
try {
    $stmt = $pdo->query("SELECT id, nome FROM departamentos ORDER BY nome");
    $departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao carregar departamentos.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $departamento_id = (int)$_POST['departamento_id'];
    $escola = trim($_POST['instituicao_ensino']);
    $sexo = $_POST['sexo'];
    $ano = $_POST['ano_estagio'];
    $nivel = $_POST['nivel_academico'];
    $bi = trim($_POST['bi_nr']);
    $emergencia = trim($_POST['contacto_emergencia']);
    $inicio = $_POST['data_inicio'] ?: date('Y-m-d');

    if ($nome && $email && $departamento_id) {
        try {
            // Inserção direta na tabela estagiarios (sem tabela usuarios)
            $sql = "INSERT INTO estagiarios (nome, email, departamento_id, instituicao_ensino, sexo, ano_estagio, nivel_academico, bi_nr, contacto_emergencia, data_inicio) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $departamento_id, $escola, $sexo, $ano, $nivel, $bi, $emergencia, $inicio]);

            $sucesso = "Estagiário registado com sucesso no arquivo histórico!";
        } catch (PDOException $e) {
            $erro = "Erro ao registar: " . $e->getMessage();
        }
    } else {
        $erro = "Por favor, preencha os campos obrigatórios (Nome, E-mail e Departamento).";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>ADM - Novo Registo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f4f7f9; font-family: 'Inter', sans-serif; }
        .card-cadastro { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .form-label { font-weight: 600; color: #334155; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">ADM CONTROLE</a>
        <div class="ms-auto">
            <a href="estagiarios.php" class="btn btn-sm btn-outline-light">Voltar à Lista</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-cadastro p-4">
                <h3 class="fw-bold mb-4"><i class="bi bi-person-plus me-2"></i>Registo de Estagiário</h3>

                <?php if($erro): ?> <div class="alert alert-danger"><?= $erro ?></div> <?php endif; ?>
                <?php if($sucesso): ?> <div class="alert alert-success"><?= $sucesso ?></div> <?php endif; ?>

                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-mail de Contacto *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sexo</label>
                        <select name="sexo" class="form-select">
                            <option value="M">Masculino</option>
                            <option value="F">Feminino</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BI Nº</label>
                        <input type="text" name="bi_nr" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Departamento *</label>
                        <select name="departamento_id" class="form-select" required>
                            <?php foreach ($departamentos as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Instituição de Ensino</label>
                        <input type="text" name="instituicao_ensino" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nível Académico</label>
                        <select name="nivel_academico" class="form-select">
                            <option value="Licenciatura">Licenciatura</option>
                            <option value="Técnico Médio">Técnico Médio</option>
                            <option value="Mestrado">Mestrado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ano do Estágio</label>
                        <input type="number" name="ano_estagio" class="form-control" value="<?= date('Y') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contacto de Emergência</label>
                        <input type="text" name="contacto_emergencia" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Data de Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <hr>
                        <button type="submit" class="btn btn-primary px-5 py-2">Finalizar Registo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>