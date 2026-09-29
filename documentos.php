<?php
$pageTitle = 'Documentos';
$currentPage = 'documentos';
$categorias = require __DIR__ . '/data/documentos.php';
include __DIR__ . '/includes/header.php';
?>
<main>
  <section class="hero">
    <div class="container">
      <h1>Documentos</h1>
      <p>Relatórios de contas, estatutos, regulamentos e convocatórias da ASSM.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="documentos-grid">
        <?php foreach ($categorias as $categoria): ?>
          <article class="documento-card">
            <div class="documento-faixa"><?= htmlspecialchars($categoria['titulo']) ?></div>
            <ul class="documento-lista">
              <?php foreach ($categoria['documentos'] as $documento): ?>
                <li><?= htmlspecialchars($documento) ?></li>
              <?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
