<?php
/**
 * Guarda de autenticação das páginas de direção (admin.php).
 * Equivalente a auth.php, mas para as contas de data/admins.php.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

$admins = require __DIR__ . '/../../data/admins.php';
$adminAtual = $admins[$_SESSION['admin_user']] ?? null;

if (!$adminAtual) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$escolas = require __DIR__ . '/../../data/escolas.php';
