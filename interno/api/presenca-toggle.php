<?php
require __DIR__ . '/_common.php';

$body = json_body();
$alunoId = (int) ($body['aluno_id'] ?? 0);
$diaId = (int) ($body['dia_id'] ?? 0);
$periodo = $body['periodo'] ?? '';

if ($alunoId <= 0 || $diaId <= 0 || !in_array($periodo, ['M', 'A', 'T'], true)) {
    respond(['ok' => false, 'erro' => 'Pedido inválido.'], 400);
}

$stmt = db()->prepare('SELECT 1 FROM alunos_presenca WHERE id = :id AND escola_slug = :s');
$stmt->execute(['id' => $alunoId, 's' => $escola]);
if (!$stmt->fetchColumn()) {
    respond(['ok' => false, 'erro' => 'Aluno não encontrado.'], 404);
}

$stmt = db()->prepare('SELECT 1 FROM presenca_dias WHERE id = :id AND escola_slug = :s');
$stmt->execute(['id' => $diaId, 's' => $escola]);
if (!$stmt->fetchColumn()) {
    respond(['ok' => false, 'erro' => 'Dia não encontrado.'], 404);
}

$stmt = db()->prepare('SELECT status FROM presenca_status WHERE aluno_id = :a AND dia_id = :d AND periodo = :p');
$stmt->execute(['a' => $alunoId, 'd' => $diaId, 'p' => $periodo]);
$statusAtual = $stmt->fetchColumn();

$ciclo = [false => 'presente', 'presente' => 'ausente', 'ausente' => false];
$proximo = $ciclo[$statusAtual === false ? false : $statusAtual];

if ($proximo === false) {
    $stmt = db()->prepare('DELETE FROM presenca_status WHERE aluno_id = :a AND dia_id = :d AND periodo = :p');
    $stmt->execute(['a' => $alunoId, 'd' => $diaId, 'p' => $periodo]);
    respond(['ok' => true, 'status' => null]);
}

$stmt = db()->prepare('
    INSERT INTO presenca_status (escola_slug, aluno_id, dia_id, periodo, status)
    VALUES (:s, :a, :d, :p, :st)
    ON CONFLICT(aluno_id, dia_id, periodo) DO UPDATE SET status = excluded.status, updated_at = datetime(\'now\')
');
$stmt->execute(['s' => $escola, 'a' => $alunoId, 'd' => $diaId, 'p' => $periodo, 'st' => $proximo]);

respond(['ok' => true, 'status' => $proximo]);
