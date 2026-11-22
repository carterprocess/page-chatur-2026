import { MAX_SLOTS } from '../config.js';
import { escapeHtml, sameModel } from '../util.js';

const ICONS = { reload: '&#8635;', max: '&#10530;', fullscreen: '&#9974;', open: '&#8599;', clear: '&#10005;' };

function cardHtml(i) {
  return `
    <article class="player-card" data-slot="${i}">
      <header class="player-bar" data-select role="button" tabindex="0" aria-label="Selecionar player ${i + 1}">
        <span class="slot-num">${i + 1}</span>
        <span class="slot-name"></span>
        <span class="slot-flag"></span>
        <span class="slot-tools">
          <button type="button" class="tool" data-act="reload" title="Recarregar">${ICONS.reload}</button>
          <button type="button" class="tool" data-act="max" title="Ampliar / reduzir (duplo clique na barra)">${ICONS.max}</button>
          <button type="button" class="tool" data-act="fullscreen" title="Tela cheia">${ICONS.fullscreen}</button>
          <a class="tool" data-act="open" title="Abrir no site" target="_blank" rel="noopener noreferrer" href="#">${ICONS.open}</a>
          <button type="button" class="tool" data-act="clear" title="Esvaziar">${ICONS.clear}</button>
        </span>
      </header>
      <div class="player-stage"></div>
    </article>`;
}

const PLACEHOLDER = '<div class="player-placeholder">Escolha uma thumb na lista<br>para exibir neste player.</div>';

function playerSrc(src, videoOnly) {
  if (!videoOnly) return src;
  try {
    const url = new URL(src);
    url.searchParams.set('embed_video_only', '1');
    return url.toString();
  } catch (_e) {
    return src;
  }
}

/** Grade de players. Cria os cartoes uma vez e sincroniza o DOM com o estado sem recarregar iframes sem necessidade. */
export function initPlayers(root, store, actions) {
  root.innerHTML = Array.from({ length: MAX_SLOTS }, (_, i) => cardHtml(i)).join('');
  const cards = [...root.querySelectorAll('.player-card')];
  const rendered = cards.map(() => ({ src: null, videoOnly: null })); // null = ainda nao desenhado

  function syncCard(card, i, state) {
    const slot = state.slots[i];
    const inLayout = i < state.layout;
    const visible = inLayout && (state.maximized < 0 || state.maximized === i);
    const model = state.models.find((m) => sameModel(m.username, slot.username));

    card.classList.toggle('is-hidden', !visible);
    card.classList.toggle('active-target', i === state.activeSlot);
    card.classList.toggle('maximized', state.maximized === i);
    card.querySelector('.slot-name').textContent = slot.username || 'vazio';

    const flag = card.querySelector('.slot-flag');
    flag.textContent = !slot.username || !state.loadedAt ? '' : (model ? model.num_users + ' views' : 'fora da lista');
    flag.classList.toggle('warn', !!slot.username && !!state.loadedAt && !model);

    const open = card.querySelector('[data-act="open"]');
    open.href = slot.username ? (model?.room_url || 'https://chaturbate.com/' + encodeURIComponent(slot.username) + '/') : '#';

    // Iframe existe so para players dentro do layout; fora dele e descarregado (economiza banda).
    const wanted = inLayout && slot.src ? slot.src : '';
    const stage = card.querySelector('.player-stage');
    const prev = rendered[i];
    if (prev.src !== wanted || (wanted && prev.videoOnly !== state.videoOnly)) {
      stage.innerHTML = wanted
        ? `<iframe class="player${state.videoOnly ? ' video-only' : ''}" src="${escapeHtml(playerSrc(wanted, state.videoOnly))}" title="Player ${i + 1}" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`
        : (inLayout ? PLACEHOLDER : '');
      rendered[i] = { src: wanted, videoOnly: state.videoOnly };
    }
  }

  function sync(state) {
    root.dataset.layout = String(state.layout);
    root.classList.toggle('has-max', state.maximized >= 0);
    cards.forEach((card, i) => syncCard(card, i, state));
  }

  function reload(i) {
    const iframe = cards[i].querySelector('iframe.player');
    if (iframe) iframe.src = iframe.src;
  }

  root.addEventListener('click', (event) => {
    const card = event.target.closest('.player-card');
    if (!card) return;
    const i = Number(card.dataset.slot);
    const tool = event.target.closest('[data-act]');

    if (tool) {
      const act = tool.dataset.act;
      if (act === 'open') return; // link normal
      event.preventDefault();
      if (act === 'reload') reload(i);
      else if (act === 'max') actions.toggleMaximize(i);
      else if (act === 'clear') actions.clearSlot(i);
      else if (act === 'fullscreen') card.querySelector('.player-stage').requestFullscreen?.().catch(() => {});
      return;
    }
    if (event.target.closest('[data-select]')) actions.setActive(i);
  });

  root.addEventListener('dblclick', (event) => {
    const bar = event.target.closest('.player-bar');
    if (bar && !event.target.closest('.slot-tools')) {
      actions.toggleMaximize(Number(bar.closest('.player-card').dataset.slot));
    }
  });

  root.addEventListener('keydown', (event) => {
    if ((event.key !== 'Enter' && event.key !== ' ') || !event.target.matches('.player-bar')) return;
    event.preventDefault();
    actions.setActive(Number(event.target.closest('.player-card').dataset.slot));
  });

  store.subscribe(['slots', 'activeSlot', 'layout', 'maximized', 'videoOnly', 'models', 'loadedAt'], sync);
  sync(store.get());

  return { reloadAll: () => cards.forEach((_, i) => reload(i)) };
}
