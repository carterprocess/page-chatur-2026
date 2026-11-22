import { escapeHtml, formatViews, formatDuration } from '../util.js';

function itemHtml(m, thumbBucket) {
  const thumb = String(m.image_url || '').trim();
  const thumbUrl = thumb ? thumb + (thumb.includes('?') ? '&' : '?') + 't=' + thumbBucket : '';
  const show = String(m.current_show || '').toLowerCase();
  const badges = [
    m.is_favorite ? '<span class="badge badge-fav" title="Favorito">&#9733;</span>' : '',
    m.is_hd ? '<span class="badge badge-hd">HD</span>' : '',
    show && show !== 'public' ? `<span class="badge badge-show">${escapeHtml(show)}</span>` : '',
    m.is_new ? '<span class="badge badge-new">NEW</span>' : ''
  ].join('');

  return `
    <article class="item" data-username="${escapeHtml(m.username.toLowerCase())}" tabindex="0" role="button" aria-label="Assistir ${escapeHtml(m.username)}">
      <div class="thumb-wrap">
        ${thumbUrl ? `<img class="thumb" src="${escapeHtml(thumbUrl)}" alt="" loading="lazy" decoding="async">` : '<div class="thumb"></div>'}
        <div class="badges">${badges}</div>
        <div class="views">${escapeHtml(formatViews(m.num_users))}</div>
      </div>
      <div class="meta">
        <div class="name" title="${escapeHtml(m.room_subject || m.username)}">${escapeHtml(m.username)}</div>
        <div class="time">${escapeHtml(formatDuration(m.seconds_online))} online</div>
      </div>
    </article>`;
}

/** Lista de modelos: redesenha so quando algo muda e marca os perfis abertos nos players. */
export function initList(root, store, actions) {
  let lastSignature = '';

  function visibleModels(state) {
    const q = state.filters.q.trim().toLowerCase();
    return q
      ? state.models.filter((m) => (m.username + ' ' + m.display_name).toLowerCase().includes(q))
      : state.models;
  }

  function markActive(state) {
    const open = new Set(state.slots.slice(0, state.layout).map((s) => s.username.toLowerCase()).filter(Boolean));
    root.querySelectorAll('.item').forEach((el) => el.classList.toggle('active', open.has(el.dataset.username)));
  }

  function render(state) {
    const models = visibleModels(state);
    const bucket = Math.floor(Date.now() / 60000); // thumbs renovadas a cada minuto
    const signature = bucket + '|' + models.map((m) => [m.username, m.num_users, m.current_show, m.is_favorite ? 1 : 0].join(':')).join(',');
    if (signature !== lastSignature) {
      lastSignature = signature;
      root.innerHTML = models.length
        ? models.map((m) => itemHtml(m, bucket)).join('')
        : `<div class="empty">${state.models.length ? 'Nenhum perfil corresponde a busca.' : 'Nenhuma modelo online com esses filtros agora.'}</div>`;
    }
    markActive(state);
  }

  root.addEventListener('click', (event) => {
    const item = event.target.closest('.item');
    if (item) actions.selectModel(item.dataset.username);
  });

  root.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    const item = event.target.closest('.item');
    if (!item) return;
    event.preventDefault();
    actions.selectModel(item.dataset.username);
  });

  store.subscribe(['models', 'filters'], render);
  store.subscribe(['slots', 'layout'], markActive);
  render(store.get());
}
