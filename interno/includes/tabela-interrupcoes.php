<?php
/**
 * Sub-aba "Interrupções/Férias" — uma tabela por período de interrupção letiva.
 * Espera $escola (slug da escola da sessão) definido pelo ficheiro que inclui este partial.
 */

$stmtInterrupcoes = db()->prepare('SELECT * FROM alunos_interrupcoes WHERE escola_slug = :s AND periodo = :p ORDER BY row_order, id');
?>
<?php foreach (PERIODOS_INTERRUPCAO as $periodo): ?>
  <?php
    alunosGarantirLinhasMinimas('alunos_interrupcoes', $escola, 10, $periodo);
    $stmtInterrupcoes->execute(['s' => $escola, 'p' => $periodo]);
    $linhasPeriodo = $stmtInterrupcoes->fetchAll();
    $slugPeriodo = str_replace(' ', '-', strtolower($periodo));
  ?>
  <h3><?= htmlspecialchars($periodo) ?></h3>
  <div class="tabela-scroll">
    <table class="tabela-alunos" data-tabela="interrupcoes" data-periodo="<?= htmlspecialchars($periodo) ?>">
      <thead>
        <tr>
          <th>N.º</th><th>NOME</th><th>Escola</th><th>Ano</th><th>NIF</th><th>Escalão</th><th>Irmão</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($linhasPeriodo as $linha): ?>
          <tr data-id="<?= (int) $linha['id'] ?>">
            <?= alunosCelulasIdentidade($linha) ?>
            <?= alunosCelulaAcoes('interrupcoes', (int) $linha['id']) ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <template id="tpl-row-interrupcoes-<?= htmlspecialchars($slugPeriodo) ?>">
    <tr data-id="0">
      <?= alunosCelulasIdentidade([]) ?>
      <?= alunosCelulaAcoes('interrupcoes', 0) ?>
    </tr>
  </template>
  <div class="tabela-acoes">
    <button type="button" class="btn btn-primary btn-add-row" data-tabela="interrupcoes" data-periodo="<?= htmlspecialchars($periodo) ?>">+ Adicionar aluno</button>
    <button type="button" class="btn btn-primary btn-guardar-tabela" data-tabela="interrupcoes" data-periodo="<?= htmlspecialchars($periodo) ?>">Guardar tabela</button>
  </div>
<?php endforeach; ?>
<div class="tabela-acoes">
  <a class="btn btn-exportar" href="api/alunos-exportar.php?tabela=interrupcoes">Exportar Excel (todos os períodos)</a>
</div>
