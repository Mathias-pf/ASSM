<?php
/**
 * Histórico de mensagens trocadas com um contacto. Abrir a conversa marca
 * como lidas as mensagens desse contacto dirigidas a quem está em sessão.
 */
require __DIR__ . '/_common-mensagens.php';

$body = json_body();
$tipo = (string) ($body['tipo'] ?? '');
$id = (string) ($body['id'] ?? '');

if ($tipo === '' || $id === '') {
    respond(['ok' => false, 'erro' => 'Pedido inválido.'], 400);
}

$resultado = mensagensThread($identidade, $tipo, $id);

if ($resultado === null) {
    respond(['ok' => false, 'erro' => 'Contacto inválido.'], 400);
}

respond([
    'ok' => true,
    'contacto' => $resultado['contacto'],
    'mensagens' => $resultado['mensagens'],
    'por_ler_total' => mensagensContarNaoLidas($identidade),
]);
