<?php
/**
 * @var string $pageTitle  set by the including page
 * @var string $currentPage  slug used to highlight the active nav item
 */
$pageTitle = $pageTitle ?? 'ASSM';
$currentPage = $currentPage ?? '';

$navItems = [
    'index'          => ['label' => 'Início',          'href' => 'index.php'],
    'sobre-nos'      => ['label' => 'Sobre Nós',        'href' => 'sobre-nos.php'],
    'projetos'       => ['label' => 'Projetos',         'href' => 'projetos.php'],
    'orgaos-sociais' => ['label' => 'Órgãos Sociais',   'href' => 'orgaos-sociais.php'],
    'documentos'     => ['label' => 'Documentos',       'href' => 'documentos.php'],
    'contato'        => ['label' => 'Contato',          'href' => 'contato.php'],
];
?><!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> — ASSM</title>
  <link rel="icon" type="image/png" href="assets/img/logo-icon.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container">
    <a href="index.php" class="logo">
      <img src="assets/img/logo-icon.png" alt="" class="logo-mark">
      <span class="logo-text">ASSM<small>Associação de Solidariedade Social da Madalena</small></span>
    </a>
    <nav class="main-nav" aria-label="Navegação principal">
      <ul>
        <?php foreach ($navItems as $slug => $item): ?>
          <li>
            <a href="<?= $item['href'] ?>" <?= $currentPage === $slug ? 'aria-current="page"' : '' ?>>
              <?= htmlspecialchars($item['label']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>
