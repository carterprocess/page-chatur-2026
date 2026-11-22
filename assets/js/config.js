// Constantes e valores padrao da aplicacao.
export const API_URL = './api/models.php';
export const REFRESH_MS = 15000;
export const STALE_AFTER_SECONDS = 300;
export const MAX_SLOTS = 4;
export const LAYOUTS = [1, 2, 4];
export const STORAGE_KEY = 'pagechat.v3';
export const EMBED_ORIGIN = 'https://chaturbate.com/';

export const DEFAULT_FILTERS = Object.freeze({
  favorites: false,
  sort: 'favorites',
  tag: '',
  region: [],
  maxAge: '',
  current_show: '',
  is_new: '',
  tokenMin: '',
  hd: false,
  q: ''
});

// Chaves do estado gravadas no navegador.
export const PERSISTED_KEYS = ['slots', 'activeSlot', 'layout', 'filters', 'videoOnly', 'filtersHidden'];
