<?php
/**
 * Lista de contactos de quem está em sessão, com pré-visualização da última
 * mensagem e contagem de não lidas — usada para atualizar a caixa de
 * entrada depois de enviar uma mensagem.
 */
require __DIR__ . '/_common-mensagens.php';

respond(['ok' => true, 'contactos' => mensagensListarContactosComPreview($identidade)]);
