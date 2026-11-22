import { STORAGE_KEY, PERSISTED_KEYS, DEFAULT_FILTERS, MAX_SLOTS, LAYOUTS, EMBED_ORIGIN } from './config.js';

const emptySlot = () => ({ username: '', src: '' });

function initialState() {
  return {
    models: [],
    dataAge: null,
    favoritesTotal: 0,
    loadedAt: null,
    error: '',
    slots: Array.from({ length: MAX_SLOTS }, emptySlot),
    activeSlot: 0,
    layout: 4,
    maximized: -1,
    filters: { ...DEFAULT_FILTERS },
    videoOnly: false,
    filtersHidden: false
  };
}

// Slots salvos por versoes antigas usam /in/?... (com redirecionamentos); converte para o embed direto.
function toDirectEmbed(src) {
  try {
    const url = new URL(src);
    const room = url.searchParams.get('room');
    if (url.pathname !== '/in/' || !room || !/^\w+$/.test(room)) return src;
    const p = new URLSearchParams({ bgcolor: url.searchParams.get('bgcolor') || 'white', disable_sound: '0', join_overlay: '1', room });
    ['campaign', 'tour', 'track'].forEach((k) => url.searchParams.has(k) && p.set(k, url.searchParams.get(k)));
    p.sort();
    return EMBED_ORIGIN + 'embed/' + room + '/?' + p.toString();
  } catch (_e) {
    return src;
  }
}

// Aceita apenas dados persistidos coerentes; ignora o resto.
function sanitize(saved) {
  const out = {};
  if (Array.isArray(saved.slots)) {
    out.slots = Array.from({ length: MAX_SLOTS }, (_, i) => {
      const s = saved.slots[i];
      return s && typeof s.src === 'string' && s.src.startsWith(EMBED_ORIGIN)
        ? { username: String(s.username || ''), src: toDirectEmbed(s.src) }
        : emptySlot();
    });
  }
  if (LAYOUTS.includes(saved.layout)) out.layout = saved.layout;
  if (Number.isInteger(saved.activeSlot) && saved.activeSlot >= 0 && saved.activeSlot < MAX_SLOTS) {
    out.activeSlot = saved.activeSlot;
  }
  if (saved.filters && typeof saved.filters === 'object') {
    out.filters = { ...DEFAULT_FILTERS, ...saved.filters };
  }
  if (typeof saved.videoOnly === 'boolean') out.videoOnly = saved.videoOnly;
  if (typeof saved.filtersHidden === 'boolean') out.filtersHidden = saved.filtersHidden;
  return out;
}

function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? sanitize(JSON.parse(raw)) : {};
  } catch (_e) {
    return {};
  }
}

function save(state) {
  try {
    const subset = {};
    PERSISTED_KEYS.forEach((k) => { subset[k] = state[k]; });
    localStorage.setItem(STORAGE_KEY, JSON.stringify(subset));
  } catch (_e) { /* armazenamento indisponivel */ }
}

/** Store minimo: get(), set(patch), subscribe(keys, fn). Notifica apenas as chaves alteradas. */
export function createStore() {
  let state = { ...initialState(), ...load() };
  const listeners = [];

  return {
    get: () => state,
    set(patch) {
      const changed = Object.keys(patch).filter((k) => state[k] !== patch[k]);
      if (!changed.length) return;
      state = { ...state, ...patch };
      if (changed.some((k) => PERSISTED_KEYS.includes(k))) save(state);
      listeners.forEach((l) => {
        if (l.keys.some((k) => changed.includes(k))) l.fn(state, changed);
      });
    },
    subscribe(keys, fn) {
      listeners.push({ keys, fn });
    }
  };
}
