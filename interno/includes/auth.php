<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['escola_user'])) {
    header('Location: login.php');
    exit;
}

$escolas = require __DIR__ . '/../../data/escolas.php';
$escolaAtual = $escolas[$_SESSION['escola_user']] ?? null;

if (!$escolaAtual) {
    session_destroy();
    header('Location: login.php');
    exit;
}
