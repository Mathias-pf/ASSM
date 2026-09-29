<?php
/**
 * Grava, numa única transação, todos os campos editados de uma tabela
 * (usado pelo botão "Guardar tabela").
 */
require __DIR__ . '/_common.php';

$body = json_body();
$tabela = $body['tabela'] ?? '';
$linhas = $body['linhas'] ?? [];

$nomeTabela = alunosNomeTabela($tabela);
$camposPermitidos = alunosCamposPermitidos($tabela);

if (!$nomeTabela || !$camposPermitidos || !is_array($linhas)) {
    respond(['ok' => false, 'erro' => 'Pedido inválido.'], 400);
}

$db = db();
$db->beginTransaction();

$colunas = implode(', ', $camposPermitidos);
$stmtAtual = $db->prepare("SELECT {$colunas} FROM {$nomeTabela} WHERE id = :id AND escola_slug = :s");

$atualizados = 0;
foreach ($linhas as $linha) {
    $id = (int) ($linha['id'] ?? 0);
    $campos = $linha['campos'] ?? [];
    if ($id <= 0 || !is_array($campos) || !$campos) {
        continue;
    }

    $stmtAtual->execute(['id' => $id, 's' => $escola]);
    $valoresAtuais = $stmtAtual->fetch();
    if (!$valoresAtuais) {
        continue;
    }

    $sets = [];
    $params = ['id' => $id, 's' => $escola];
    foreach ($campos as $campo => $valor) {
        if (!in_array($campo, $camposPermitidos, true)) {
            continue;
        }

        if ($campo === 'nif') {
            $valor = substr(preg_replace('/\D/', '', (string) $valor), 0, 9);
        }
        if ($campo === 'irmao' && $valor !== '' && !in_array($valor, IRMAO_OPCOES, true)) {
            continue;
        }
        if ($campo === 'servico' && $valor !== '' && !in_array($valor, SERVICO_OPCOES, true)) {
            continue;
        }
        if ($campo === 'escalao' && $valor !== '' && !in_array($valor, ESCALAO_OPCOES, true)) {
            continue;
        }

        $valor = $valor === '' ? null : $valor;

        // Só grava o que mudou: caso contrário updated_at deixaria de indicar
        // atividade real e o painel de direção encheria de linhas em branco.
        if ($valor === $valoresAtuais[$campo]) {
            continue;
        }

        $sets[] = "{$campo} = :{$campo}";
        $params[$campo] = $valor;
    }

    if (!$sets) {
        continue;
    }

    $sql = "UPDATE {$nomeTabela} SET " . implode(', ', $sets) . ", updated_at = datetime('now') WHERE id = :id AND escola_slug = :s";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $atualizados++;
}

$db->commit();

respond(['ok' => true, 'atualizados' => $atualizados]);
