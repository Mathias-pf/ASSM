// Abas genéricas (nível de topo, sub-abas, e a grelha de escolas do painel
// de direção — esta última marca data-group diretamente no botão em vez de
// num wrapper .tabs, por isso o grupo é lido de .closest('[data-group]')).
function tabsGrupoDoBotao(btn) {
  return btn.closest('[data-group]').dataset.group;
}

function tabsIrmaosDoGrupo(grupo) {
  return document.querySelectorAll(
    '.tabs[data-group="' + grupo + '"] .tab-btn, .tab-btn[data-group="' + grupo + '"]'
  );
}

function tabsAtivar(grupo, tab) {
  const alvo = 'tab-' + tab;

  tabsIrmaosDoGrupo(grupo).forEach((b) => {
    b.setAttribute('aria-selected', b.dataset.tab === tab ? 'true' : 'false');
  });

  document.querySelectorAll('.tab-panel[data-group="' + grupo + '"]').forEach((painel) => {
    painel.toggleAttribute('hidden', painel.id !== alvo);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.tab-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      tabsAtivar(tabsGrupoDoBotao(btn), btn.dataset.tab);
    });
  });
});
