<?php
require __DIR__ . '/_common.php';

$body = json_body();
$tabela = $body['tabela'] ?? '';
$id = (int) ($body['id'] ?? 0);
$campo = $body['campo'] ?? '';
$valor = $body['valor'] ?? '';

$nomeTabela = alunosNomeTabela($tabela);
$camposPermitidos = alunosCamposPermitidos($tabela);

if (!$nomeTabela || !$camposPermitidos || !in_array($campo, $camposPermitidos, true) || $id <= 0) {
    respond(['ok' => false, 'erro' => 'Pedido inválido.'], 400);
}

if ($campo === 'nif') {
    $valor = substr(preg_replace('/\D/', '', (string) $valor), 0, 9);
}

if ($campo === 'irmao' && $valor !== '' && !in_array($valor, IRMAO_OPCOES, true)) {
    respond(['ok' => false, 'erro' => 'Valor inválido.'], 400);
}

if ($campo === 'servico' && $valor !== '' && !in_array($valor, SERVICO_OPCOES, true)) {
    respond(['ok' => false, 'erro' => 'Valor inválido.'], 400);
}

if ($campo === 'escalao' && $valor !== '' && !in_array($valor, ESCALAO_OPCOES, true)) {
    respond(['ok' => false, 'erro' => 'Valor inválido.'], 400);
}

$valor = $valor === '' ? null : $valor;

$stmt = db()->prepare("SELECT {$campo} FROM {$nomeTabela} WHERE id = :id AND escola_slug = :s");
$stmt->execute(['id' => $id, 's' => $escola]);
$linha = $stmt->fetch();

if (!$linha) {
    respond(['ok' => false, 'erro' => 'Registo não encontrado.'], 404);
}

// Sair de um campo sem o alterar não conta como gravação: manter updated_at
// intacto é o que permite ao painel de direção mostrar atividade real.
if ($linha[$campo] === $valor) {
    respond(['ok' => true]);
}

$stmt = db()->prepare("UPDATE {$nomeTabela} SET {$campo} = :valor, updated_at = datetime('now') WHERE id = :id AND escola_slug = :s");
$stmt->execute(['valor' => $valor, 'id' => $id, 's' => $escola]);

respond(['ok' => true]);
