<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tipo_usuario = $_SESSION['tipo'] ?? '';
$nome_exibicao = $_SESSION['nome'] ?? 'Utilizador';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
       <img src="assets\aeroportos.jpg" height="80"></i>ADM CONTROLE
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
           
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active fw-bold' : '' ?>" href="dashboard.php">
                       Dashboard
                    </a>
                </li>

                <?php if (in_array($tipo_usuario, ['admin', 'supervisor'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'estagiarios.php' ? 'active fw-bold' : '' ?>" href="estagiarios.php">
                       Estagiários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'presencas.php' ? 'active fw-bold' : '' ?>" href="presencas.php">
                          </i> Presenças
                        </a>
                    </li>
                    <?php if ($tipo_usuario === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'departamentos.php' ? 'active fw-bold' : '' ?>" href="departamentos.php">
                               Departamentos
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center">
                <span class="navbar-text me-3 text-light d-none d-lg-inline">
                    Olá, <strong><?= htmlspecialchars($nome_exibicao) ?></strong>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-1"></i>Sair
                </a>
            </div>
        </div>
    </div>
</nav>