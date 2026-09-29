<?php
require __DIR__ . '/includes/auth-admin.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/admin-atividade.php';
require __DIR__ . '/includes/alunos-schema.php';
require __DIR__ . '/includes/mensagens.php';

$dia = date('Y-m-d');

$atividadePorEscola = [];
foreach ($escolas as $slug => $dados) {
    $atividadePorEscola[$slug] = adminAtividadeDoDia($slug, $dia);
}

$mensagensIdentidadeAdmin = mensagensIdentidadeAtual();
$porLerPorEscola = [];
foreach (mensagensListarContactosComPreview($mensagensIdentidadeAdmin) as $contacto) {
    if ($contacto['tipo'] === 'escola' && $contacto['por_ler'] > 0) {
        $porLerPorEscola[$contacto['id']] = $contacto['por_ler'];
    }
}

$primeiraEscola = array_key_first($escolas);

$pageTitle = 'Direção';
include __DIR__ . '/includes/header.php';
?>
<main>
  <section class="section content-page">
    <div class="container">
      <h1>Painel de Direção</h1>
      <p class="section-lead">Atividade de <?= htmlspecialchars(date('d/m/Y')) ?> — escolha uma escola para ver o que foi registado hoje.</p>

      <div class="tabs" data-group="topo" role="tablist">
        <button type="button" class="tab-btn" data-tab="escolas" aria-selected="true" role="tab">Escolas</button>
        <button type="button" class="tab-btn" data-tab="mensagens" aria-selected="false" role="tab">Mensagens</button>
      </div>

      <section class="tab-panel" data-group="topo" id="tab-escolas" role="tabpanel">
        <div class="escolas-grid" data-group="escolas" role="tablist">
          <?php foreach ($escolas as $slug => $dados): ?>
            <button type="button" class="escola-btn tab-btn" data-group="escolas" data-tab="escola-<?= htmlspecialchars($slug) ?>"
                    role="tab" aria-selected="<?= $slug === $primeiraEscola ? 'true' : 'false' ?>">
              <span class="escola-nome"><?= htmlspecialchars($dados['nome']) ?></span>
              <span class="escola-local"><?= htmlspecialchars($dados['localidade']) ?></span>
              <?php if ($atividadePorEscola[$slug]['total'] > 0): ?>
                <span class="escola-badge"><?= (int) $atividadePorEscola[$slug]['total'] ?></span>
              <?php endif; ?>
              <?php if (!empty($porLerPorEscola[$slug])): ?>
                <span class="escola-resposta-badge">✉ <?= (int) $porLerPorEscola[$slug] ?></span>
              <?php endif; ?>
            </button>
          <?php endforeach; ?>
        </div>

        <?php foreach ($escolas as $slug => $dados): ?>
          <?php $atividade = $atividadePorEscola[$slug]; ?>
          <section class="tab-panel" data-group="escolas" id="tab-escola-<?= htmlspecialchars($slug) ?>"
                   role="tabpanel" <?= $slug === $primeiraEscola ? '' : 'hidden' ?>>
            <h2><?= htmlspecialchars($dados['nome']) ?> <small class="escola-local-titulo"><?= htmlspecialchars($dados['localidade']) ?></small></h2>

            <div class="tabela-acoes">
              <a class="btn btn-exportar" href="api/alunos-exportar.php?tabela=mensalidades&amp;escola=<?= urlencode($slug) ?>">Exportar Mensalidades</a>
              <a class="btn btn-exportar" href="api/alunos-exportar.php?tabela=interrupcoes&amp;escola=<?= urlencode($slug) ?>">Exportar Interrupções</a>
              <a class="btn btn-exportar" href="api/alunos-exportar.php?tabela=presenca&amp;escola=<?= urlencode($slug) ?>">Exportar Presença</a>
              <button type="button" class="btn btn-primary btn-mensagem-escola" data-abrir-mensagem="escola:<?= htmlspecialchars($slug) ?>">✉ Enviar mensagem</button>
            </div>

            <h3>Registado hoje</h3>
            <?php if ($atividade['total'] === 0 && $atividade['marcacoes_presenca'] === 0 && !$atividade['dias_novos']): ?>
              <p class="admin-vazio">Ainda não foi registado nada nesta escola hoje.</p>
            <?php else: ?>
              <?php if ($atividade['mensalidades']): ?>
                <h4>Mensalidades (<?= count($atividade['mensalidades']) ?>)</h4>
                <div class="tabela-scroll">
                  <table class="tabela-alunos">
                    <thead><tr><th>N.º</th><th>Nome</th><th>Ano</th><th>Serviço</th><th>Hora</th></tr></thead>
                    <tbody>
                      <?php foreach ($atividade['mensalidades'] as $linha): ?>
                        <tr>
                          <td><?= htmlspecialchars($linha['numero'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['nome'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['ano'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['servico'] ?? '') ?></td>
                          <td><?= htmlspecialchars(adminHoraLocal($linha['updated_at'])) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <?php if ($atividade['interrupcoes']): ?>
                <h4>Interrupções/Férias (<?= count($atividade['interrupcoes']) ?>)</h4>
                <div class="tabela-scroll">
                  <table class="tabela-alunos">
                    <thead><tr><th>Período</th><th>N.º</th><th>Nome</th><th>Ano</th><th>Hora</th></tr></thead>
                    <tbody>
                      <?php foreach ($atividade['interrupcoes'] as $linha): ?>
                        <tr>
                          <td><?= htmlspecialchars($linha['periodo'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['numero'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['nome'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['ano'] ?? '') ?></td>
                          <td><?= htmlspecialchars(adminHoraLocal($linha['updated_at'])) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <?php if ($atividade['presenca']): ?>
                <h4>Presença Diária (<?= count($atividade['presenca']) ?>)</h4>
                <div class="tabela-scroll">
                  <table class="tabela-alunos">
                    <thead><tr><th>N.º</th><th>Nome</th><th>Ano</th><th>Serviço</th><th>Hora</th></tr></thead>
                    <tbody>
                      <?php foreach ($atividade['presenca'] as $linha): ?>
                        <tr>
                          <td><?= htmlspecialchars($linha['numero'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['nome'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['ano'] ?? '') ?></td>
                          <td><?= htmlspecialchars($linha['servico'] ?? '') ?></td>
                          <td><?= htmlspecialchars(adminHoraLocal($linha['updated_at'])) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>

              <?php if ($atividade['marcacoes_presenca'] > 0 || $atividade['dias_novos']): ?>
                <p class="admin-resumo">
                  <?php if ($atividade['marcacoes_presenca'] > 0): ?>
                    <?= (int) $atividade['marcacoes_presenca'] ?> marcação(ões) de presença registada(s) hoje.
                  <?php endif; ?>
                  <?php if ($atividade['dias_novos']): ?>
                    Dias adicionados: <?= htmlspecialchars(implode(', ', array_map(
                      fn ($data) => date('d/m/Y', strtotime($data)),
                      $atividade['dias_novos']
                    ))) ?>.
                  <?php endif; ?>
                </p>
              <?php endif; ?>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>
      </section>

      <section class="tab-panel" data-group="topo" id="tab-mensagens" role="tabpanel" hidden>
        <?php include __DIR__ . '/includes/mensagens-painel.php'; ?>
      </section>
    </div>
  </section>
</main>

<script src="../assets/js/tabs.js" defer></script>
<script src="../assets/js/mensagens.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
