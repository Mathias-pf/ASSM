<?php
/**
 * Sub-aba "Presença Diária" — grelha de presença (Manhã/Almoço/Tarde) por dia.
 * Espera $escola (slug da escola da sessão) definido pelo ficheiro que inclui este partial.
 */

alunosGarantirLinhasMinimas('alunos_presenca', $escola, 10);

$stmt = db()->prepare('SELECT * FROM alunos_presenca WHERE escola_slug = :s ORDER BY row_order, id');
$stmt->execute(['s' => $escola]);
$alunosPresenca = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM presenca_dias WHERE escola_slug = :s ORDER BY row_order, id');
$stmt->execute(['s' => $escola]);
$diasPresenca = $stmt->fetchAll();

$stmt = db()->prepare('SELECT aluno_id, dia_id, periodo, status FROM presenca_status WHERE escola_slug = :s');
$stmt->execute(['s' => $escola]);
$statusMap = [];
foreach ($stmt->fetchAll() as $s) {
    $statusMap[$s['aluno_id'] . ':' . $s['dia_id'] . ':' . $s['periodo']] = $s['status'];
}

function alunosFormatarDia(string $data): string
{
    $ts = strtotime($data);
    return $ts ? date('d-M', $ts) : $data;
}

function alunosCelulaPresenca(int $alunoId, int $diaId, string $periodo, ?string $status): string
{
    return '<td><button type="button" class="toggle-presenca" data-aluno-id="' . $alunoId
        . '" data-dia-id="' . $diaId . '" data-periodo="' . $periodo
        . '" data-status="' . htmlspecialchars($status ?? '') . '">'
        . ($status === 'presente' ? '✓' : ($status === 'ausente' ? '✗' : ''))
        . '</button></td>';
}
?>
<div class="presenca-add-dia">
  <label for="novo-dia">Adicionar dia</label>
  <input type="date" id="novo-dia">
  <button type="button" class="btn btn-primary" id="btn-add-dia">Adicionar dia</button>
</div>
<div class="tabela-scroll">
  <table class="tabela-alunos" data-tabela="presenca">
    <thead>
      <tr>
        <th rowspan="2">N.º</th><th rowspan="2">NOME</th><th rowspan="2">Escola</th><th rowspan="2">Ano</th>
        <th rowspan="2">NIF</th><th rowspan="2">Escalão</th><th rowspan="2">Irmão</th><th rowspan="2">Serviço</th>
        <?php foreach ($diasPresenca as $dia): ?>
          <th colspan="3" data-dia-id="<?= (int) $dia['id'] ?>"><?= htmlspecialchars(alunosFormatarDia($dia['data'])) ?></th>
        <?php endforeach; ?>
        <th rowspan="2">Ações</th>
      </tr>
      <tr>
        <?php foreach ($diasPresenca as $dia): ?>
          <th>M</th><th>A</th><th>T</th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($alunosPresenca as $aluno): ?>
        <tr data-id="<?= (int) $aluno['id'] ?>">
          <?= alunosCelulasIdentidade($aluno) ?>
          <td><?= alunosCampoSelect('servico', SERVICO_OPCOES, $aluno['servico']) ?></td>
          <?php foreach ($diasPresenca as $dia): ?>
            <?php foreach (['M', 'A', 'T'] as $periodo): ?>
              <?= alunosCelulaPresenca((int) $aluno['id'], (int) $dia['id'], $periodo, $statusMap[$aluno['id'] . ':' . $dia['id'] . ':' . $periodo] ?? null) ?>
            <?php endforeach; ?>
          <?php endforeach; ?>
          <?= alunosCelulaAcoes('presenca', (int) $aluno['id']) ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <template id="tpl-row-presenca">
    <tr data-id="0">
      <?= alunosCelulasIdentidade([]) ?>
      <td><?= alunosCampoSelect('servico', SERVICO_OPCOES, null) ?></td>
      <?php foreach ($diasPresenca as $dia): ?>
        <?php foreach (['M', 'A', 'T'] as $periodo): ?>
          <?= alunosCelulaPresenca(0, (int) $dia['id'], $periodo, null) ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?= alunosCelulaAcoes('presenca', 0) ?>
    </tr>
  </template>
</div>
<div class="tabela-acoes">
  <button type="button" class="btn btn-primary btn-add-row" data-tabela="presenca">+ Adicionar aluno</button>
  <button type="button" class="btn btn-primary btn-guardar-tabela" data-tabela="presenca">Guardar tabela</button>
  <a class="btn btn-exportar" href="api/alunos-exportar.php?tabela=presenca">Exportar Excel</a>
</div>
