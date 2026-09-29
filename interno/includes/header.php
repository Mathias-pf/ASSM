<?php
require_once __DIR__ . '/mensagens.php';

$pageTitle = $pageTitle ?? 'Área Interna';
$mensagensPorLer = 0;

if (!empty($escolaAtual) || !empty($adminAtual)) {
    $mensagensIdentidadeHeader = mensagensIdentidadeAtual();
    if ($mensagensIdentidadeHeader !== null) {
        $mensagensPorLer = mensagensContarNaoLidas($mensagensIdentidadeHeader);
    }
}
?><!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> — ASSM</title>
  <link rel="icon" type="image/png" href="../assets/img/logo-icon.png">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="interno-header">
  <div class="container">
    <a href="../index.php" class="logo">
      <img src="../assets/img/logo-icon.png" alt="" class="logo-mark">
      <span class="logo-text">ASSM<small><?= !empty($adminAtual) ? 'Direção' : 'Área de Funcionários' ?></small></span>
    </a>
    <?php if (!empty($escolaAtual) || !empty($adminAtual)): ?>
      <div class="header-acoes">
        <button type="button" class="btn-sino" id="btn-sino" aria-label="Mensagens">
          ✉
          <?php if ($mensagensPorLer > 0): ?>
            <span class="sino-badge" id="sino-badge"><?= $mensagensPorLer ?></span>
          <?php endif; ?>
        </button>
        <a href="logout.php" class="btn btn-primary">Sair</a>
      </div>
    <?php endif; ?>
  </div>
</header>
