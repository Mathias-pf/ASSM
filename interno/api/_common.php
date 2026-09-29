<?php
/**
 * Bootstrap partilhado pelos endpoints JSON de "Alunos".
 * Ao contrário de includes/auth.php, nunca redireciona — responde sempre JSON,
 * mesmo em caso de sessão inválida, para não quebrar fetch().json() no cliente.
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

if (empty($_SESSION['escola_user'])) {
    respond(['ok' => false, 'erro' => 'Sessão expirada.'], 401);
}

$escola = $_SESSION['escola_user'];

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/alunos-schema.php';

function json_body(): array
{
    $dados = json_decode(file_get_contents('php://input'), true);
    return is_array($dados) ? $dados : [];
}
