<?php
/**
 * Bootstrap partilhado pelos endpoints JSON da direção.
 * Igual ao _common.php mas exige uma sessão de administrador.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function respond(array $dados, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($dados);
    exit;
}

if (empty($_SESSION['admin_user'])) {
    respond(['ok' => false, 'erro' => 'Sessão expirada.'], 401);
}

$admins = require __DIR__ . '/../../data/admins.php';
$adminAtual = $admins[$_SESSION['admin_user']] ?? null;

if (!$adminAtual) {
    respond(['ok' => false, 'erro' => 'Sessão inválida.'], 401);
}

$escolas = require __DIR__ . '/../../data/escolas.php';

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/alunos-schema.php';

function json_body(): array
{
    $dados = json_decode(file_get_contents('php://input'), true);
    return is_array($dados) ? $dados : [];
}
