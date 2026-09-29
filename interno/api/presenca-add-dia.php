<?php
require __DIR__ . '/_common.php';

$body = json_body();
$data = $body['data'] ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    respond(['ok' => false, 'erro' => 'Data inválida.'], 400);
}

$stmt = db()->prepare('SELECT COALESCE(MAX(row_order), 0) + 1 FROM presenca_dias WHERE escola_slug = :s');
$stmt->execute(['s' => $escola]);
$rowOrder = (int) $stmt->fetchColumn();

try {
    $stmt = db()->prepare('INSERT INTO presenca_dias (escola_slug, data, row_order) VALUES (:s, :d, :o)');
    $stmt->execute(['s' => $escola, 'd' => $data, 'o' => $rowOrder]);
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'UNIQUE')) {
        respond(['ok' => false, 'erro' => 'Esse dia já existe.'], 409);
    }
    throw $e;
}

$ts = strtotime($data);
respond([
    'ok' => true,
    'dia_id' => (int) db()->lastInsertId(),
    'data' => $data,
    'rotulo' => $ts ? date('d-M', $ts) : $data,
    'row_order' => $rowOrder,
]);
