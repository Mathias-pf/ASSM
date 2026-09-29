<?php
/**
 * Sub-aba "Mensalidades" — tabela larga de frequência/mensalidades por aluno.
 * Espera $escola (slug da escola da sessão) definido pelo ficheiro que inclui este partial.
 */

alunosGarantirLinhasMinimas('alunos_mensalidades', $escola, 10);

$stmt = db()->prepare('SELECT * FROM alunos_mensalidades WHERE escola_slug = :s ORDER BY row_order, id');
$stmt->execute(['s' => $escola]);
$linhasMensalidades = $stmt->fetchAll();
?>
<div class="tabela-scroll">
  <table class="tabela-alunos" data-tabela="mensalidades">
    <thead>
      <tr>
        <th>N.º</th><th>NOME</th><th>Escola</th><th>Ano</th><th>NIF</th><th>Escalão</th><th>Irmão</th>
        <th>Serviço</th><th>Seguro</th><th>Mês seg</th>
        <?php foreach (MESES as $rotulo): ?>
          <th><?= htmlspecialchars($rotulo) ?></th><th>Desc</th>
        <?php endforeach; ?>
        <th>IL Novembro</th><th>IL Natal</th><th>IL Fim Semestre</th><th>IL Páscoa</th><th>F Verão</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($linhasMensalidades as $linha): ?>
        <tr data-id="<?= (int) $linha['id'] ?>">
          <?= alunosCelulasIdentidade($linha) ?>
          <td><?= alunosCampoSelect('servico', SERVICO_OPCOES, $linha['servico']) ?></td>
          <td><?= alunosCampoTexto('seguro', $linha['seguro']) ?></td>
          <td><?= alunosCampoTexto('mes_seg', $linha['mes_seg']) ?></td>
          <?php foreach (array_keys(MESES) as $mes): ?>
            <td><?= alunosCampoTexto('mes_' . $mes, $linha['mes_' . $mes]) ?></td>
            <td><?= alunosCampoTexto('desc_' . $mes, $linha['desc_' . $mes]) ?></td>
          <?php endforeach; ?>
          <td><?= alunosCampoTexto('il_novembro', $linha['il_novembro']) ?></td>
          <td><?= alunosCampoTexto('il_natal', $linha['il_natal']) ?></td>
          <td><?= alunosCampoTexto('il_fim_semestre', $linha['il_fim_semestre']) ?></td>
          <td><?= alunosCampoTexto('il_pascoa', $linha['il_pascoa']) ?></td>
          <td><?= alunosCampoTexto('f_verao', $linha['f_verao']) ?></td>
          <?= alunosCelulaAcoes('mensalidades', (int) $linha['id']) ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <template id="tpl-row-mensalidades">
    <tr data-id="0">
      <?= alunosCelulasIdentidade([]) ?>
      <td><?= alunosCampoSelect('servico', SERVICO_OPCOES, null) ?></td>
      <td><?= alunosCampoTexto('seguro', null) ?></td>
      <td><?= alunosCampoTexto('mes_seg', null) ?></td>
      <?php foreach (array_keys(MESES) as $mes): ?>
        <td><?= alunosCampoTexto('mes_' . $mes, null) ?></td>
        <td><?= alunosCampoTexto('desc_' . $mes, null) ?></td>
      <?php endforeach; ?>
      <td><?= alunosCampoTexto('il_novembro', null) ?></td>
      <td><?= alunosCampoTexto('il_natal', null) ?></td>
      <td><?= alunosCampoTexto('il_fim_semestre', null) ?></td>
      <td><?= alunosCampoTexto('il_pascoa', null) ?></td>
      <td><?= alunosCampoTexto('f_verao', null) ?></td>
      <?= alunosCelulaAcoes('mensalidades', 0) ?>
    </tr>
  </template>
</div>
<div class="tabela-acoes">
  <button type="button" class="btn btn-primary btn-add-row" id="btn-add-mensalidades" data-tabela="mensalidades">+ Adicionar aluno</button>
  <button type="button" class="btn btn-primary btn-guardar-tabela" data-tabela="mensalidades">Guardar tabela</button>
  <a class="btn btn-exportar" href="api/alunos-exportar.php?tabela=mensalidades">Exportar Excel</a>
</div>
