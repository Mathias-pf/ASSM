<?php
/**
 * Bootstrap partilhado pelos endpoints JSON de mensagens. Ao contrário de
 * _common.php/_common-admin.php, aceita QUALQUER sessão válida (escola ou
 * funcionário), porque ambos os lados usam os mesmos endpoints.
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

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/alunos-schema.php';
require __DIR__ . '/../includes/mensagens.php';

$identidade = mensagensIdentidadeAtual();

if ($identidade === null) {
    respond(['ok' => false, 'erro' => 'Sessão expirada.'], 401);
}

function json_body(): array
{
    $dados = json_decode(file_get_contents('php://input'), true);
    return is_array($dados) ? $dados : [];
}
