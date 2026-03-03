<?php
// includes/conexao.php

// Configurações (mude a senha se tiver definido no MySQL)
$host = 'localhost';
$dbname = 'estagiarios_aeroportos';
$usuario = 'root';
$senha = '';  // ← mude se tiver senha!

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          // Lança exceções em erro
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Retorna array associativo
            PDO::ATTR_EMULATE_PREPARES => false                   // Usa prepared statements reais (mais seguro)
        ]
    );
} catch (PDOException $e) {
    // Em produção, logar erro e mostrar mensagem amigável
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Função auxiliar para queries preparadas (facilita uso depois)
function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
?>