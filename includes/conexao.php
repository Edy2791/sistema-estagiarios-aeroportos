<?php


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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     
            PDO::ATTR_EMULATE_PREPARES => false                   
        ]
    );
} catch (PDOException $e) {
  
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}


function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
?>