import { DEFAULT_FILTERS } from '../config.js';
import { debounce } from '../util.js';

function read(form) {
  const el = form.elements;
  return {
    favorites: el.favorites.checked,
    sort: el.sort.value,
    tag: el.tag.value.trim(),
    region: [...form.querySelectorAll('input[name="region"]:checked')].map((i) => i.value),
    maxAge: el.maxAge.value,
    current_show: el.current_show.value,
    is_new: el.is_new.value,
    tokenMin: el.tokenMin.value,
    hd: el.hd.checked,
    q: el.q.value.trim()
  };
}

function write(form, f) {
  const el = form.elements;
  el.favorites.checked = !!f.favorites;
  el.sort.value = f.sort;
  el.tag.value = f.tag;
  form.querySelectorAll('input[name="region"]').forEach((i) => { i.checked = f.region.includes(i.value); });
  el.maxAge.value = f.maxAge;
  el.current_show.value = f.current_show;
  el.is_new.value = f.is_new;
  el.tokenMin.value = f.tokenMin;
  el.hd.checked = !!f.hd;
  el.q.value = f.q;
}

/** Formulario de filtros: aplica automaticamente (com debounce) e reflete o estado salvo. */
export function initFilters(form, store) {
  write(form, store.get().filters);

  const commit = () => store.set({ filters: read(form) });
  const commitDebounced = debounce(commit, 300);

  form.addEventListener('submit', (e) => { e.preventDefault(); commit(); });
  form.addEventListener('input', (e) => {
    if (e.target.matches('input[type="text"], input[type="search"], input[type="number"]')) commitDebounced();
  });
  form.addEventListener('change', commit);

  form.querySelector('[data-clear]').addEventListener('click', () => {
    write(form, DEFAULT_FILTERS);
    commit();
  });
}
