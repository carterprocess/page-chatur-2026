import { MAX_SLOTS } from '../config.js';

/** Botoes da barra lateral: layout, favoritos, recarregar players, filtros e modo de player. */
export function initControls(root, store, actions, players) {
  const layoutButtons = [...root.querySelectorAll('[data-layout]')];
  const filtersPanel = root.querySelector('.filters-grid');
  const toggleFilters = root.querySelector('[data-toggle-filters]');
  const videoOnly = root.querySelector('[data-video-only]');

  function sync(state) {
    layoutButtons.forEach((b) => b.classList.toggle('is-active', Number(b.dataset.layout) === state.layout));
    filtersPanel.hidden = state.filtersHidden;
    toggleFilters.textContent = state.filtersHidden ? 'Mostrar filtros' : 'Ocultar filtros';
    videoOnly.checked = state.videoOnly;
  }

  layoutButtons.forEach((b) => b.addEventListener('click', () => actions.setLayout(Number(b.dataset.layout))));
  root.querySelector('[data-fill]').addEventListener('click', actions.fillFromFavorites);
  root.querySelector('[data-reload]').addEventListener('click', players.reloadAll);
  toggleFilters.addEventListener('click', () => store.set({ filtersHidden: !store.get().filtersHidden }));
  videoOnly.addEventListener('change', () => store.set({ videoOnly: videoOnly.checked }));

  // Atalhos: 1..4 selecionam o player.
  document.addEventListener('keydown', (event) => {
    if (event.ctrlKey || event.metaKey || event.altKey || event.target.matches('input, select, textarea')) return;
    const n = Number(event.key);
    if (n >= 1 && n <= MAX_SLOTS) actions.setActive(n - 1);
  });

  store.subscribe(['layout', 'filtersHidden', 'videoOnly'], sync);
  sync(store.get());
}
