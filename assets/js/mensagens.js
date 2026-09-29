// Caixa de entrada partilhada por interno/index.php (escola) e
// interno/admin.php (funcionários): lista de contactos + conversa com um
// deles. Espera includes/mensagens-painel.php já renderizado na página
// (fornece #contactos-lista, #conversa-*, <template id="tpl-balao-mensagem">)
// e tabs.js já carregado (fornece tabsAtivar).

document.addEventListener('DOMContentLoaded', () => {
  const listaContactos = document.getElementById('contactos-lista');
  if (!listaContactos) return; // página sem painel de mensagens

  const conversaVazio = document.getElementById('conversa-vazio');
  const conversaAtiva = document.getElementById('conversa-ativa');
  const conversaNome = document.getElementById('conversa-nome');
  const conversaThread = document.getElementById('conversa-thread');
  const conversaCompose = document.getElementById('conversa-compose');
  const destinoTipo = document.getElementById('conversa-destino-tipo');
  const destinoId = document.getElementById('conversa-destino-id');
  const campoCategoria = document.getElementById('conversa-categoria');
  const campoCorpo = document.getElementById('conversa-corpo');
  const tplBalao = document.getElementById('tpl-balao-mensagem');

  function formatarData(isoUtc) {
    const data = new Date(isoUtc.replace(' ', 'T') + 'Z');
    const dois = (n) => String(n).padStart(2, '0');
    return dois(data.getDate()) + '/' + dois(data.getMonth() + 1) + '/' + data.getFullYear()
      + ' ' + dois(data.getHours()) + ':' + dois(data.getMinutes());
  }

  function atualizarBadgeSino(porLer) {
    const btnSino = document.getElementById('btn-sino');
    let badge = document.getElementById('sino-badge');
    if (porLer > 0) {
      if (!badge && btnSino) {
        badge = document.createElement('span');
        badge.className = 'sino-badge';
        badge.id = 'sino-badge';
        btnSino.appendChild(badge);
      }
      if (badge) badge.textContent = porLer;
    } else if (badge) {
      badge.remove();
    }
  }

  function renderBalao(mensagem) {
    const li = tplBalao.content.firstElementChild.cloneNode(true);
    li.classList.add(mensagem.de_mim ? 'balao-propria' : 'balao-recebida');
    li.querySelector('.balao-categoria').textContent = mensagem.categoria || 'Geral';
    li.querySelector('.balao-corpo').textContent = mensagem.corpo;
    li.querySelector('.balao-data').textContent = (mensagem.de_mim ? '' : mensagem.autor + ' · ') + formatarData(mensagem.created_at);
    return li;
  }

  function marcarContactoAtivo(tipo, id) {
    listaContactos.querySelectorAll('.contacto-item').forEach((item) => {
      item.classList.toggle('is-ativo', item.dataset.tipo === tipo && item.dataset.id === id);
    });
  }

  function abrirConversa(tipo, id) {
    fetch('api/mensagens-conversa.php', {
      method: 'POST',
      body: JSON.stringify({ tipo, id }),
    })
      .then((r) => r.json())
      .then((resposta) => {
        if (!resposta.ok) {
          alert(resposta.erro || 'Não foi possível abrir a conversa.');
          return;
        }

        conversaVazio.hidden = true;
        conversaAtiva.hidden = false;
        conversaNome.textContent = resposta.contacto.nome
          + (resposta.contacto.localidade ? ' (' + resposta.contacto.localidade + ')' : '');
        destinoTipo.value = tipo;
        destinoId.value = id;

        conversaThread.innerHTML = '';
        resposta.mensagens.forEach((mensagem) => conversaThread.appendChild(renderBalao(mensagem)));
        conversaThread.scrollTop = conversaThread.scrollHeight;

        marcarContactoAtivo(tipo, id);
        const itemAtivo = listaContactos.querySelector('.contacto-item[data-tipo="' + tipo + '"][data-id="' + id + '"] .contacto-badge');
        if (itemAtivo) itemAtivo.remove();

        atualizarBadgeSino(resposta.por_ler_total);
      })
      .catch(() => alert('Erro de ligação ao abrir a conversa.'));
  }

  listaContactos.addEventListener('click', (e) => {
    const item = e.target.closest('.contacto-item');
    if (!item) return;
    abrirConversa(item.dataset.tipo, item.dataset.id);
  });

  if (conversaCompose) {
    conversaCompose.addEventListener('submit', (e) => {
      e.preventDefault();

      const corpo = campoCorpo.value.trim();
      if (!corpo) return;

      const btnEnviar = conversaCompose.querySelector('button[type="submit"]');
      btnEnviar.disabled = true;

      fetch('api/mensagem-enviar.php', {
        method: 'POST',
        body: JSON.stringify({
          tipo: destinoTipo.value,
          id: destinoId.value,
          categoria: campoCategoria.value,
          corpo,
        }),
      })
        .then((r) => r.json())
        .then((resposta) => {
          btnEnviar.disabled = false;

          if (!resposta.ok) {
            alert(resposta.erro || 'Não foi possível enviar a mensagem.');
            return;
          }

          conversaThread.appendChild(renderBalao(resposta.mensagem));
          conversaThread.scrollTop = conversaThread.scrollHeight;
          campoCorpo.value = '';
          atualizarBadgeSino(resposta.por_ler_total);
          atualizarListaContactos();
        })
        .catch(() => {
          btnEnviar.disabled = false;
          alert('Erro de ligação ao enviar a mensagem.');
        });
    });
  }

  function renderContacto(contacto) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'contacto-item';
    btn.dataset.tipo = contacto.tipo;
    btn.dataset.id = contacto.id;

    const linha1 = document.createElement('span');
    linha1.className = 'contacto-linha1';

    const nome = document.createElement('span');
    nome.className = 'contacto-nome';
    nome.textContent = contacto.nome;
    if (contacto.localidade) {
      const small = document.createElement('small');
      small.className = 'contacto-localidade';
      small.textContent = contacto.localidade;
      nome.appendChild(document.createTextNode(' '));
      nome.appendChild(small);
    }
    linha1.appendChild(nome);

    if (contacto.por_ler > 0) {
      const badge = document.createElement('span');
      badge.className = 'contacto-badge';
      badge.textContent = contacto.por_ler;
      linha1.appendChild(badge);
    }

    btn.appendChild(linha1);

    if (contacto.ultima_corpo !== null) {
      const preview = document.createElement('span');
      preview.className = 'contacto-preview';
      preview.textContent = (contacto.ultima_de_mim ? 'Eu: ' : '') + contacto.ultima_corpo;
      btn.appendChild(preview);
    }

    return btn;
  }

  function atualizarListaContactos() {
    const tipoAtivo = destinoTipo.value;
    const idAtivo = destinoId.value;

    fetch('api/mensagens-contactos.php')
      .then((r) => r.json())
      .then((resposta) => {
        if (!resposta.ok) return;

        listaContactos.innerHTML = '';
        resposta.contactos.forEach((contacto) => listaContactos.appendChild(renderContacto(contacto)));

        if (tipoAtivo && idAtivo) marcarContactoAtivo(tipoAtivo, idAtivo);
      });
  }

  // --- Atalhos que saltam para a aba Mensagens -----------------------------
  const btnSino = document.getElementById('btn-sino');
  if (btnSino) {
    btnSino.addEventListener('click', () => tabsAtivar('topo', 'mensagens'));
  }

  document.querySelectorAll('[data-abrir-mensagem]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const [tipo, id] = btn.dataset.abrirMensagem.split(':');
      tabsAtivar('topo', 'mensagens');
      abrirConversa(tipo, id);
    });
  });
});
