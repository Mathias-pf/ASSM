document.addEventListener('DOMContentLoaded', () => {
  // --- Filtro do NIF: só dígitos, máximo 9 -------------------------------
  document.addEventListener('input', (e) => {
    if (e.target.matches('[data-campo="nif"]')) {
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 9);
    }
  });

  // --- Indicador de gravação junto à linha ------------------------------
  function mostrarIndicador(tr, ok, mensagem) {
    const indicador = tr.querySelector('.save-indicator');
    if (!indicador) return;
    indicador.textContent = mensagem;
    indicador.classList.toggle('is-erro', !ok);
    indicador.classList.add('is-visible');
    setTimeout(() => indicador.classList.remove('is-visible'), 1500);
  }

  // --- Autosave ao sair do campo (delegado por tabela) -------------------
  document.addEventListener('focusout', (e) => {
    const campo = e.target.closest('[data-campo]');
    if (!campo) return;

    const tabelaEl = e.target.closest('table[data-tabela]');
    const tr = e.target.closest('tr[data-id]');
    if (!tabelaEl || !tr) return;

    const id = parseInt(tr.dataset.id, 10);
    if (!id) return; // linha ainda não guardada no servidor

    fetch('api/alunos-save.php', {
      method: 'POST',
      body: JSON.stringify({
        tabela: tabelaEl.dataset.tabela,
        id,
        campo: campo.dataset.campo,
        valor: campo.value,
      }),
    })
      .then((r) => r.json())
      .then((resposta) => mostrarIndicador(tr, resposta.ok, resposta.ok ? 'Guardado' : resposta.erro))
      .catch(() => mostrarIndicador(tr, false, 'Erro de ligação'));
  });

  // --- Adicionar aluno ----------------------------------------------------
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-add-row');
    if (!btn) return;

    const tabela = btn.dataset.tabela;
    const periodo = btn.dataset.periodo || null;

    fetch('api/alunos-add-row.php', {
      method: 'POST',
      body: JSON.stringify({ tabela, periodo }),
    })
      .then((r) => r.json())
      .then((resposta) => {
        if (!resposta.ok) {
          alert(resposta.erro || 'Não foi possível adicionar o aluno.');
          return;
        }

        let templateId = 'tpl-row-' + tabela;
        if (tabela === 'interrupcoes') {
          templateId += '-' + periodo.toLowerCase().replace(/\s+/g, '-');
        }
        const template = document.getElementById(templateId);
        const novaLinha = template.content.firstElementChild.cloneNode(true);
        novaLinha.dataset.id = resposta.id;
        novaLinha.querySelector('.btn-delete-row').dataset.id = resposta.id;
        novaLinha.querySelectorAll('.toggle-presenca').forEach((b) => (b.dataset.alunoId = resposta.id));

        const tabelaEl = periodo
          ? document.querySelector('table[data-tabela="' + tabela + '"][data-periodo="' + periodo + '"]')
          : document.querySelector('table[data-tabela="' + tabela + '"]');

        // Se dias foram adicionados à presença depois do carregamento da página, o
        // template (gerado no carregamento) não os tem — completa-se com os que faltam.
        if (tabela === 'presenca') {
          const diasHeader = Array.from(tabelaEl.querySelectorAll('thead tr:first-child th[data-dia-id]')).map((th) => th.dataset.diaId);
          const jaTem = novaLinha.querySelectorAll('.toggle-presenca').length / 3;
          for (let i = jaTem; i < diasHeader.length; i++) {
            ['M', 'A', 'T'].forEach((p) => {
              const td = document.createElement('td');
              const b = document.createElement('button');
              b.type = 'button';
              b.className = 'toggle-presenca';
              b.dataset.alunoId = resposta.id;
              b.dataset.diaId = diasHeader[i];
              b.dataset.periodo = p;
              b.dataset.status = '';
              td.appendChild(b);
              novaLinha.insertBefore(td, novaLinha.lastElementChild);
            });
          }
        }

        tabelaEl.querySelector('tbody').appendChild(novaLinha);
      })
      .catch(() => alert('Erro de ligação ao adicionar aluno.'));
  });

  // --- Guardar tabela (lote) ----------------------------------------------
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-guardar-tabela');
    if (!btn) return;

    const tabela = btn.dataset.tabela;
    const periodo = btn.dataset.periodo || null;

    const tabelaEl = periodo
      ? document.querySelector('table[data-tabela="' + tabela + '"][data-periodo="' + periodo + '"]')
      : document.querySelector('table[data-tabela="' + tabela + '"]');
    if (!tabelaEl) return;

    const linhas = Array.from(tabelaEl.querySelectorAll('tbody tr[data-id]'))
      .map((tr) => {
        const campos = {};
        tr.querySelectorAll('[data-campo]').forEach((campo) => {
          campos[campo.dataset.campo] = campo.value;
        });
        return { id: parseInt(tr.dataset.id, 10), campos };
      })
      .filter((linha) => linha.id > 0);

    const textoOriginal = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'A guardar...';

    fetch('api/alunos-guardar-lote.php', {
      method: 'POST',
      body: JSON.stringify({ tabela, linhas }),
    })
      .then((r) => r.json())
      .then((resposta) => {
        btn.disabled = false;
        btn.textContent = textoOriginal;
        if (!resposta.ok) {
          alert(resposta.erro || 'Não foi possível guardar a tabela.');
          return;
        }
        alert('Tabela guardada com sucesso.');
      })
      .catch(() => {
        btn.disabled = false;
        btn.textContent = textoOriginal;
        alert('Erro de ligação ao guardar a tabela.');
      });
  });

  // --- Remover aluno --------------------------------------------------------
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-delete-row');
    if (!btn) return;
    if (!confirm('Remover este aluno?')) return;

    const tr = btn.closest('tr[data-id]');
    const tabelaEl = btn.closest('table[data-tabela]');

    fetch('api/alunos-delete-row.php', {
      method: 'POST',
      body: JSON.stringify({ tabela: tabelaEl.dataset.tabela, id: parseInt(tr.dataset.id, 10) }),
    })
      .then((r) => r.json())
      .then((resposta) => {
        if (resposta.ok) {
          tr.remove();
        } else {
          alert(resposta.erro || 'Não foi possível remover o aluno.');
        }
      })
      .catch(() => alert('Erro de ligação ao remover aluno.'));
  });

  // --- Presença: alternar estado ao clicar ------------------------------
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.toggle-presenca');
    if (!btn) return;

    fetch('api/presenca-toggle.php', {
      method: 'POST',
      body: JSON.stringify({
        aluno_id: parseInt(btn.dataset.alunoId, 10),
        dia_id: parseInt(btn.dataset.diaId, 10),
        periodo: btn.dataset.periodo,
      }),
    })
      .then((r) => r.json())
      .then((resposta) => {
        if (!resposta.ok) {
          alert(resposta.erro || 'Não foi possível gravar a presença.');
          return;
        }
        btn.dataset.status = resposta.status || '';
        btn.textContent = resposta.status === 'presente' ? '✓' : resposta.status === 'ausente' ? '✗' : '';
      })
      .catch(() => alert('Erro de ligação ao gravar a presença.'));
  });

  // --- Presença: adicionar novo dia ---------------------------------------
  const btnAddDia = document.getElementById('btn-add-dia');
  if (btnAddDia) {
    btnAddDia.addEventListener('click', () => {
      const input = document.getElementById('novo-dia');
      if (!input.value) return;

      fetch('api/presenca-add-dia.php', {
        method: 'POST',
        body: JSON.stringify({ data: input.value }),
      })
        .then((r) => r.json())
        .then((resposta) => {
          if (!resposta.ok) {
            alert(resposta.erro || 'Não foi possível adicionar o dia.');
            return;
          }

          const tabela = document.querySelector('table[data-tabela="presenca"]');
          const [linhaData, linhaPeriodos] = tabela.querySelectorAll('thead tr');

          const thData = document.createElement('th');
          thData.colSpan = 3;
          thData.dataset.diaId = resposta.dia_id;
          thData.textContent = resposta.rotulo;
          linhaData.insertBefore(thData, linhaData.lastElementChild);

          ['M', 'A', 'T'].forEach((p) => {
            const th = document.createElement('th');
            th.textContent = p;
            linhaPeriodos.appendChild(th);
          });

          tabela.querySelectorAll('tbody tr').forEach((tr) => {
            const alunoId = parseInt(tr.dataset.id, 10);
            ['M', 'A', 'T'].forEach((p) => {
              const td = document.createElement('td');
              const b = document.createElement('button');
              b.type = 'button';
              b.className = 'toggle-presenca';
              b.dataset.alunoId = alunoId;
              b.dataset.diaId = resposta.dia_id;
              b.dataset.periodo = p;
              b.dataset.status = '';
              td.appendChild(b);
              tr.insertBefore(td, tr.lastElementChild);
            });
          });

          input.value = '';
        })
        .catch(() => alert('Erro de ligação ao adicionar o dia.'));
    });
  }
});
