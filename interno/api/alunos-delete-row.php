<?php
require __DIR__ . '/_common.php';

$body = json_body();
$tabela = $body['tabela'] ?? '';
$id = (int) ($body['id'] ?? 0);

$nomeTabela = alunosNomeTabela($tabela);
if (!$nomeTabela || $id <= 0) {
    respond(['ok' => false, 'erro' => 'Pedido inválido.'], 400);
}

$stmt = db()->prepare("DELETE FROM {$nomeTabela} WHERE id = :id AND escola_slug = :s");
$stmt->execute(['id' => $id, 's' => $escola]);

if ($stmt->rowCount() === 0) {
    respond(['ok' => false, 'erro' => 'Registo não encontrado.'], 404);
}

respond(['ok' => true]);
