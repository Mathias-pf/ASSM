<?php
/**
 * Envia uma mensagem de quem está em sessão (escola ou funcionário) para um
 * contacto permitido (outro funcionário, ou — só para funcionários — uma
 * escola).
 */
require __DIR__ . '/_common-mensagens.php';

$body = json_body();
$tipo = (string) ($body['tipo'] ?? '');
$id = (string) ($body['id'] ?? '');
$categoria = (string) ($body['categoria'] ?? '');
$corpo = (string) ($body['corpo'] ?? '');

if ($tipo === '' || $id === '') {
    respond(['ok' => false, 'erro' => 'Destinatário inválido.'], 400);
}

$resultado = mensagensEnviar($identidade, $tipo, $id, $categoria, $corpo);

if (!$resultado['ok']) {
    respond($resultado, 400);
}

respond([
    'ok' => true,
    'mensagem' => $resultado['mensagem'],
    'por_ler_total' => mensagensContarNaoLidas($identidade),
]);
