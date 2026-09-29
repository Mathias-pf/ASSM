<?php
require __DIR__ . '/_common.php';

$body = json_body();
$tabela = $body['tabela'] ?? '';
$periodo = $body['periodo'] ?? null;

$nomeTabela = alunosNomeTabela($tabela);
if (!$nomeTabela) {
    respond(['ok' => false, 'erro' => 'Pedido inválido.'], 400);
}

if ($tabela === 'interrupcoes') {
    if (!in_array($periodo, PERIODOS_INTERRUPCAO, true)) {
        respond(['ok' => false, 'erro' => 'Período inválido.'], 400);
    }
    $stmt = db()->prepare('SELECT COALESCE(MAX(row_order), 0) + 1 FROM alunos_interrupcoes WHERE escola_slug = :s AND periodo = :p');
    $stmt->execute(['s' => $escola, 'p' => $periodo]);
    $rowOrder = (int) $stmt->fetchColumn();

    $stmt = db()->prepare('INSERT INTO alunos_interrupcoes (escola_slug, periodo, row_order) VALUES (:s, :p, :o)');
    $stmt->execute(['s' => $escola, 'p' => $periodo, 'o' => $rowOrder]);
} else {
    $stmt = db()->prepare("SELECT COALESCE(MAX(row_order), 0) + 1 FROM {$nomeTabela} WHERE escola_slug = :s");
    $stmt->execute(['s' => $escola]);
    $rowOrder = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("INSERT INTO {$nomeTabela} (escola_slug, row_order) VALUES (:s, :o)");
    $stmt->execute(['s' => $escola, 'o' => $rowOrder]);
}

respond(['ok' => true, 'id' => (int) db()->lastInsertId(), 'row_order' => $rowOrder]);
