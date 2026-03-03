<?php
require_once 'includes/init.php';

if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $remember = isset($_POST['remember']);

    if ($email && $senha) {
        $stmt = query("SELECT id, nome, senha, tipo FROM usuarios WHERE email = ?", [$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['tipo'] = $user['tipo'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiracao = date('Y-m-d H:i:s', strtotime('+30 days'));
                query("INSERT INTO remember_tokens (usuario_id, token, expiracao) VALUES (?, ?, ?)", [$user['id'], $token, $expiracao]);
                setcookie('remember_token', $token, time() + 2592000, "/", "", true, true);
            }

            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'Email ou senha incorretos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}

// Verificar cookie "Lembrar-me"
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = query("SELECT usuario_id FROM remember_tokens WHERE token = ? AND expiracao > NOW()", [$token]);
    $row = $stmt->fetch();

    if ($row) {
        $user_id = $row['usuario_id'];
        $stmt = query("SELECT id, nome, tipo FROM usuarios WHERE id = ?", [$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['tipo'] = $user['tipo'];
            header('Location: dashboard.php');
            exit;
        }
    }
    setcookie('remember_token', '', time() - 3600, "/");
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle de Estagiários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow p-4">
                    <h3 class="text-center mb-4">Login</h3>
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-control" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Lembrar-me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>