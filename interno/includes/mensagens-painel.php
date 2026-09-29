<?php
/**
 * Caixa de entrada (contactos + conversa) — incluído tal e qual por
 * index.php (escola) e admin.php (funcionários) dentro da aba "Mensagens".
 * Espera db.php, alunos-schema.php e mensagens.php já incluídos, e a sessão
 * (escola ou funcionário) já validada pela página que inclui este ficheiro.
 */
$mensagensIdentidade = mensagensIdentidadeAtual();
$mensagensContactos = mensagensListarContactosComPreview($mensagensIdentidade);
?>
<div class="mensagens-layout">
  <div class="contactos-lista" id="contactos-lista">
    <?php if (!$mensagensContactos): ?>
      <p class="mensagens-vazio">Ainda não há contactos disponíveis.</p>
    <?php else: ?>
      <?php foreach ($mensagensContactos as $contacto): ?>
        <button type="button" class="contacto-item" data-tipo="<?= htmlspecialchars($contacto['tipo']) ?>" data-id="<?= htmlspecialchars($contacto['id']) ?>">
          <span class="contacto-linha1">
            <span class="contacto-nome">
              <?= htmlspecialchars($contacto['nome']) ?>
              <?php if ($contacto['localidade']): ?>
                <small class="contacto-localidade"><?= htmlspecialchars($contacto['localidade']) ?></small>
              <?php endif; ?>
            </span>
            <?php if ($contacto['por_ler'] > 0): ?>
              <span class="contacto-badge"><?= (int) $contacto['por_ler'] ?></span>
            <?php endif; ?>
          </span>
          <?php if ($contacto['ultima_corpo'] !== null): ?>
            <span class="contacto-preview"><?= $contacto['ultima_de_mim'] ? 'Eu: ' : '' ?><?= htmlspecialchars($contacto['ultima_corpo']) ?></span>
          <?php endif; ?>
        </button>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="conversa-painel">
    <div class="conversa-vazio" id="conversa-vazio">
      <p>Selecione uma conversa à esquerda.</p>
    </div>
    <div class="conversa-ativa" id="conversa-ativa" hidden>
      <div class="conversa-topo">
        <h3 id="conversa-nome"></h3>
      </div>
      <ul class="conversa-thread" id="conversa-thread"></ul>
      <form class="conversa-compose" id="conversa-compose">
        <input type="hidden" id="conversa-destino-tipo">
        <input type="hidden" id="conversa-destino-id">
        <select id="conversa-categoria">
          <?php foreach (MENSAGEM_CATEGORIA_OPCOES as $opcaoCategoria): ?>
            <option value="<?= htmlspecialchars($opcaoCategoria) ?>"><?= htmlspecialchars($opcaoCategoria) ?></option>
          <?php endforeach; ?>
        </select>
        <textarea id="conversa-corpo" rows="2" maxlength="2000" placeholder="Escreva uma mensagem..." required></textarea>
        <button type="submit" class="btn btn-primary">Enviar</button>
      </form>
    </div>
  </div>
</div>
<template id="tpl-balao-mensagem">
  <li class="balao">
    <span class="balao-categoria"></span>
    <p class="balao-corpo"></p>
    <span class="balao-data"></span>
  </li>
</template>
