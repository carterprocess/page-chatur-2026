import { STALE_AFTER_SECONDS } from '../config.js';
import { formatDuration } from '../util.js';

/** Barra de status e erro. Retorna notify(msg) para avisos temporarios. */
export function initStatus(statusEl, errorEl, store) {
  let flashText = '';
  let flashTimer = null;

  function render(state) {
    errorEl.textContent = state.error ? 'Falha ao carregar: ' + state.error : '';
    errorEl.hidden = !state.error;

    const stale = state.dataAge !== null && state.dataAge > STALE_AFTER_SECONDS;
    let text;
    if (flashText) {
      text = flashText;
    } else if (!state.loadedAt) {
      text = state.error ? 'Erro na atualizacao' : 'Carregando...';
    } else {
      const favs = state.models.filter((m) => m.is_favorite).length;
      text = state.models.length + ' online • ' + favs + ' favoritos • player ativo ' + (state.activeSlot + 1) +
        ' • ' + state.loadedAt.toLocaleTimeString('pt-BR') +
        (stale ? ' • dados com ' + formatDuration(state.dataAge) : '');
    }
    statusEl.textContent = text;
    statusEl.classList.toggle('stale', stale && !flashText);
  }

  function notify(message) {
    flashText = message;
    clearTimeout(flashTimer);
    flashTimer = setTimeout(() => { flashText = ''; render(store.get()); }, 3000);
    render(store.get());
  }

  store.subscribe(['models', 'dataAge', 'error', 'loadedAt', 'activeSlot'], render);
  render(store.get());
  return notify;
}
