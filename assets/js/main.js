import { REFRESH_MS } from './config.js';
import { createStore } from './store.js';
import { createActions } from './actions.js';
import { buildParams, fetchModels } from './api.js';
import { initPlayers } from './ui/players.js';
import { initList } from './ui/list.js';
import { initFilters } from './ui/filters.js';
import { initControls } from './ui/controls.js';
import { initStatus } from './ui/status.js';

const $ = (selector) => document.querySelector(selector);

const store = createStore();
const notify = initStatus($('#status'), $('#error'), store);
const actions = createActions(store, notify);
const players = initPlayers($('#players-grid'), store, actions);
initList($('#list'), store, actions);
initFilters($('#filters-form'), store);
initControls($('#sidebar'), store, actions, players);

// ---- carga de dados ----
let controller = null;
let timer = null;

async function load() {
  controller?.abort();
  controller = new AbortController();
  try {
    const data = await fetchModels(store.get().filters, controller.signal);
    store.set({ ...data, error: '', loadedAt: new Date() });
  } catch (err) {
    if (err.name !== 'AbortError') store.set({ error: err.message });
  }
}

function startPolling() {
  clearInterval(timer);
  timer = setInterval(load, REFRESH_MS);
}

// Refaz a busca apenas quando um filtro enviado ao servidor muda (a busca por nome e local).
let lastQuery = buildParams(store.get().filters).toString();
store.subscribe(['filters'], (state) => {
  const query = buildParams(state.filters).toString();
  if (query !== lastQuery) {
    lastQuery = query;
    load();
    startPolling();
  }
});

// Pausa a atualizacao com a aba oculta.
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    clearInterval(timer);
  } else {
    load();
    startPolling();
  }
});

load();
startPolling();
