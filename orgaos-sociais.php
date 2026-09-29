<?php
$pageTitle = 'Órgãos Sociais';
$currentPage = 'orgaos-sociais';
$orgaos = require __DIR__ . '/data/orgaos-sociais.php';
include __DIR__ . '/includes/header.php';
?>
<main>
  <section class="section content-page">
    <div class="container">
      <h1 class="orgaos-title">Órgãos Sociais 2024-2028</h1>
      <div class="orgaos-grid">
        <?php foreach ($orgaos as $bloco): ?>
          <div class="orgaos-block">
            <h3><?= htmlspecialchars($bloco['titulo']) ?></h3>
            <ul>
              <?php foreach ($bloco['membros'] as $membro): ?>
                <li><strong><?= htmlspecialchars($membro['cargo']) ?>:</strong> <?= htmlspecialchars($membro['nome']) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
