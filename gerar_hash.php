<?php
$senha_plana = '123456';  // ou qualquer senha que queiras usar
$hash = password_hash($senha_plana, PASSWORD_DEFAULT);

echo "<h3>Hash gerado:</h3>";
echo "<pre style='font-size: 18px; word-break: break-all;'>" . $hash . "</pre>";
echo "<p>Copie TUDO isso acima (incluindo os $ iniciais) e cole no SQL.</p>";
?>