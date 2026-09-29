<?php
$pageTitle = 'Projetos';
$currentPage = 'projetos';
$projetos = require __DIR__ . '/data/projetos.php';
include __DIR__ . '/includes/header.php';
?>
<main>
  <section class="hero">
    <div class="container">
      <h1>Os Nossos Projetos</h1>
      <p>Conheça as respostas sociais da ASSM. Clique em "Saber mais" para conhecer melhor cada projeto.</p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="project-grid">
        <?php foreach ($projetos as $i => $p): ?>
          <?php $panelId = 'detalhe-' . $p['slug']; ?>
          <article class="project-card">
            <div class="thumb">
              <img src="<?= htmlspecialchars($p['imagem']) ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy">
            </div>
            <div class="project-body">
              <h3><?= htmlspecialchars($p['nome']) ?></h3>
              <p class="lead"><?= htmlspecialchars($p['breve']) ?></p>
              <button type="button" class="saber-mais" aria-expanded="false" data-target="<?= $panelId ?>">
                <span class="label">Saber mais</span> <span class="chevron">▾</span>
              </button>
            </div>
            <div class="project-details" id="<?= $panelId ?>">
              <p><?= htmlspecialchars($p['detalhe']) ?></p>
              <ul class="project-links">
                <?php foreach ($p['links'] as $link): ?>
                  <li><a href="<?= htmlspecialchars($link['href']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
