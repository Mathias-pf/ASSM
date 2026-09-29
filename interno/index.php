<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/alunos-schema.php';
require __DIR__ . '/includes/mensagens.php';

$escola = $_SESSION['escola_user'];

$pageTitle = 'Área de Funcionários';
include __DIR__ . '/includes/header.php';
?>
<main>
  <section class="section content-page">
    <div class="container">
      <h1>Bem-vindo(a), <?= htmlspecialchars($escolaAtual['nome']) ?></h1>
      <p class="section-lead"><?= htmlspecialchars($escolaAtual['localidade']) ?></p>

      <div class="tabs" data-group="topo" role="tablist">
        <button type="button" class="tab-btn" data-tab="inicio" aria-selected="true" role="tab">Início</button>
        <button type="button" class="tab-btn" data-tab="alunos" aria-selected="false" role="tab">Alunos</button>
        <button type="button" class="tab-btn" data-tab="mensagens" aria-selected="false" role="tab">Mensagens</button>
      </div>

      <section class="tab-panel" data-group="topo" id="tab-inicio" role="tabpanel">
        <p>Esta área está a ser preparada. Em breve terá aqui os conteúdos e documentos da sua escola.</p>
      </section>

      <section class="tab-panel" data-group="topo" id="tab-alunos" role="tabpanel" hidden>
        <div class="tabs" data-group="alunos" role="tablist">
          <button type="button" class="tab-btn" data-tab="mensalidades" aria-selected="true" role="tab">Mensalidades</button>
          <button type="button" class="tab-btn" data-tab="interrupcoes" aria-selected="false" role="tab">Interrupções/Férias</button>
          <button type="button" class="tab-btn" data-tab="presenca" aria-selected="false" role="tab">Presença Diária</button>
        </div>

        <section class="tab-panel" data-group="alunos" id="tab-mensalidades" role="tabpanel">
          <?php include __DIR__ . '/includes/tabela-mensalidades.php'; ?>
        </section>

        <section class="tab-panel" data-group="alunos" id="tab-interrupcoes" role="tabpanel" hidden>
          <?php include __DIR__ . '/includes/tabela-interrupcoes.php'; ?>
        </section>

        <section class="tab-panel" data-group="alunos" id="tab-presenca" role="tabpanel" hidden>
          <?php include __DIR__ . '/includes/tabela-presenca.php'; ?>
        </section>
      </section>

      <section class="tab-panel" data-group="topo" id="tab-mensagens" role="tabpanel" hidden>
        <?php include __DIR__ . '/includes/mensagens-painel.php'; ?>
      </section>
    </div>
  </section>
</main>
<script src="../assets/js/tabs.js" defer></script>
<script src="../assets/js/mensagens.js" defer></script>
<script src="../assets/js/alunos.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
