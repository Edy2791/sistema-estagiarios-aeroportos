<?php


require_once 'conexao.php';
require_once 'session_handler.php';


$handler = new DbSessionHandler($pdo);
session_set_save_handler($handler, true);
session_start();
?>