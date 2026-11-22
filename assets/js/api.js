import { API_URL } from './config.js';

/** Converte o estado dos filtros em parametros da API. */
export function buildParams(filters) {
  const p = new URLSearchParams();
  p.set('gender', 'f');
  p.set('sort', filters.sort || 'favorites');
  if (filters.favorites) p.set('favorites', '1');
  (filters.region || []).forEach((r) => p.append('region', r));
  if (filters.tag) p.set('tag', filters.tag.trim());
  if (filters.maxAge && Number(filters.maxAge) < 99) p.set('maxAge', filters.maxAge);
  if (filters.current_show) p.set('current_show', filters.current_show);
  if (filters.is_new !== '') p.set('is_new', filters.is_new);
  if (filters.tokenMin !== '') p.set('tokenMin', filters.tokenMin);
  if (filters.hd) p.set('hd', '1');
  return p;
}

export async function fetchModels(filters, signal) {
  const params = buildParams(filters);
  params.set('_', Date.now().toString());
  const res = await fetch(API_URL + '?' + params.toString(), { cache: 'no-store', signal });
  if (!res.ok) throw new Error('HTTP ' + res.status);
  const data = await res.json();
  if (!data.ok) throw new Error(data.error || 'Erro ao carregar dados');
  return {
    models: Array.isArray(data.results) ? data.results : [],
    dataAge: data.data_age_seconds ?? null,
    favoritesTotal: Number(data.favorites_total || 0)
  };
}
