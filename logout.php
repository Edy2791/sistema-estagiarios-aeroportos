<?php
require_once 'includes/init.php';

if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    query("DELETE FROM remember_tokens WHERE token = ?", [$token]);
    setcookie('remember_token', '', time() - 3600, "/", "", true, true);
}

session_destroy();
header('Location: index.php');
exit;
?>