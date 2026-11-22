<?php
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PAGECHAT</title>
  <link rel="preconnect" href="https://chaturbate.com">
  <link rel="preconnect" href="https://thumb.live.mmcdn.com">
  <link rel="stylesheet" href="./styles.css">
</head>
<body>
  <main class="layout">
    <section class="col-main">
      <div id="players-grid" class="players-grid"></div>
    </section>

    <aside class="col-side">
      <div id="status" class="status" role="status" aria-live="polite">Atualizando...</div>
      <div id="error" class="error" role="alert"></div>

      <form id="filters-form" class="filters-panel">
        <div class="quick-actions">
          <button type="button" id="refresh-players" class="btn" title="Recarrega os 4 players">Recarregar players</button>
          <button type="button" id="toggle-filters" class="btn">Minimizar filtros</button>
        </div>

        <div class="favorites-toggle-wrap">
          <label class="btn favorites-toggle-btn" for="onlyFavorites">
            <input id="onlyFavorites" type="checkbox">
            <span class="favorites-toggle-label">Apenas favoritos da lista fixa</span>
          </label>
        </div>

        <div class="filter-group">
          <input id="nameSearch" type="search" class="filter-control" placeholder="Buscar perfil pelo nome..." autocomplete="off">
        </div>

        <div class="filters-grid">
          <div class="filter-row">
            <div class="filter-group">
              <label for="sort">Ordenacao</label>
              <select id="sort" name="sort" class="filter-control">
                <option value="viewers">Mais views</option>
                <option value="recent">Mais recentes</option>
              </select>
            </div>

            <div class="filter-group">
              <label for="tag">Tag</label>
              <input id="tag" name="tag" class="filter-control" placeholder="ex: new">
            </div>
          </div>

          <div class="filter-group">
            <label>Regiao</label>
            <div class="filter-options">
              <label class="filter-chip"><input type="checkbox" name="region" value="northamerica">NA</label>
              <label class="filter-chip"><input type="checkbox" name="region" value="southamerica">SA</label>
              <label class="filter-chip"><input type="checkbox" name="region" value="europe_russia">EU</label>
              <label class="filter-chip"><input type="checkbox" name="region" value="asia">AS</label>
              <label class="filter-chip"><input type="checkbox" name="region" value="other">OUT</label>
            </div>
          </div>

          <input type="hidden" name="gender" value="f">

          <div class="filter-row">
            <div class="filter-group">
              <label for="maxAge">Idade max</label>
              <input id="maxAge" name="maxAge" type="number" min="18" max="99" class="filter-control" value="99">
            </div>

            <div class="filter-group">
              <label for="current_show">Show</label>
              <select id="current_show" name="current_show" class="filter-control">
                <option value="">Todos</option>
                <option value="public">Public</option>
                <option value="private">Private</option>
                <option value="group">Group</option>
                <option value="away">Away</option>
              </select>
            </div>
          </div>

          <div class="filter-row">
            <div class="filter-group">
              <label for="is_new">Novas</label>
              <select id="is_new" name="is_new" class="filter-control">
                <option value="">Todas</option>
                <option value="true">Somente novas</option>
                <option value="false">Nao novas</option>
              </select>
            </div>

            <div class="filter-group">
              <label for="tokenMin">Token min</label>
              <input id="tokenMin" name="tokenMin" type="number" min="0" class="filter-control" placeholder="0">
            </div>
          </div>

          <div class="filter-row">
            <label class="filter-chip"><input id="onlyHd" type="checkbox">Somente HD</label>
            <label class="filter-chip" title="Tenta ocultar o chat do embed (recorte desligado). Se o player ficar em branco, desmarque."><input id="videoOnly" type="checkbox">Player sem recorte</label>
          </div>

          <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <button type="button" id="clear-filters" class="btn">Limpar</button>
          </div>
        </div>
      </form>

      <div id="list" class="list"></div>
    </aside>
  </main>

  <script>
    const API_SEARCH_URL = './api-search-models.php';
    const API_FAVORITES_URL = './api-online-models.php';
    const REFRESH_MS = 15000;
    const SLOT_COUNT = 4;
    const STORAGE_KEY = 'pagechat.v2';

    const listEl = document.getElementById('list');
    const statusEl = document.getElementById('status');
    const errorEl = document.getElementById('error');
    const playersGridEl = document.getElementById('players-grid');
    const refreshPlayersBtn = document.getElementById('refresh-players');
    const filtersForm = document.getElementById('filters-form');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const onlyFavoritesEl = document.getElementById('onlyFavorites');
    const favoritesToggleBtn = document.querySelector('.favorites-toggle-btn');
    const toggleFiltersBtn = document.getElementById('toggle-filters');
    const filtersGrid = filtersForm.querySelector('.filters-grid');
    const nameSearchEl = document.getElementById('nameSearch');
    const onlyHdEl = document.getElementById('onlyHd');
    const videoOnlyEl = document.getElementById('videoOnly');

    let currentModels = [];
    let activeSlot = 0;
    let maximizedSlot = -1;
    let lastListSignature = '';
    let loading = false;
    let loadTimer = null;
    const slots = Array.from({ length: SLOT_COUNT }, () => ({ username: '', src: '' }));

    // ---------- persistencia ----------
    function saveState() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
          slots,
          activeSlot,
          onlyFavorites: onlyFavoritesEl.checked,
          videoOnly: videoOnlyEl.checked,
          onlyHd: onlyHdEl.checked,
          filtersHidden: filtersGrid.style.display === 'none'
        }));
      } catch (_e) { /* storage indisponivel */ }
    }

    function restoreState() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const s = JSON.parse(raw);
        if (Array.isArray(s.slots)) {
          s.slots.slice(0, SLOT_COUNT).forEach((slot, i) => {
            if (slot && typeof slot.src === 'string' && /^https:\/\/chaturbate\.com\//.test(slot.src)) {
              slots[i] = { username: String(slot.username || ''), src: slot.src };
            }
          });
        }
        activeSlot = Math.min(SLOT_COUNT - 1, Math.max(0, Number(s.activeSlot || 0)));
        onlyFavoritesEl.checked = !!s.onlyFavorites;
        videoOnlyEl.checked = !!s.videoOnly;
        onlyHdEl.checked = !!s.onlyHd;
        if (s.filtersHidden) setFiltersHidden(true);
      } catch (_e) { /* ignora estado invalido */ }
    }

    // ---------- utilitarios ----------
    function escapeHtml(str) {
      return String(str || '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
      }[ch]));
    }

    function secondsToHuman(seconds) {
      const s = Number(seconds || 0);
      if (s < 3600) return Math.floor(s / 60) + ' min';
      return (s / 3600).toFixed(1) + ' h';
    }

    function formatViews(n) {
      n = Number(n || 0);
      return n >= 1000 ? (n / 1000).toFixed(1).replace('.0', '') + 'k' : String(n);
    }

    // Monta a URL do player: fundo preto e, opcionalmente, somente video.
    function buildPlayerSrc(src, forceRefresh) {
      try {
        const url = new URL(src, window.location.href);
        url.searchParams.set('bgcolor', 'black');
        if (videoOnlyEl.checked) {
          url.searchParams.set('embed_video_only', '1');
        } else {
          url.searchParams.delete('embed_video_only');
        }
        if (forceRefresh) url.searchParams.set('_r', Date.now().toString());
        return url.toString();
      } catch (_e) {
        return src;
      }
    }

    function buildQueryFromFilters() {
      const params = new URLSearchParams();
      params.set('sort', document.getElementById('sort').value || 'viewers');
      document.querySelectorAll('input[name="region"]:checked').forEach((i) => params.append('region', i.value));
      params.set('gender', 'f');

      const tag = document.getElementById('tag').value.trim();
      if (tag) params.set('tag', tag);
      const maxAge = document.getElementById('maxAge').value;
      if (maxAge) params.set('maxAge', maxAge);
      const currentShow = document.getElementById('current_show').value;
      if (currentShow) params.set('current_show', currentShow);
      const isNew = document.getElementById('is_new').value;
      if (isNew !== '') params.set('is_new', isNew);
      const tokenMin = document.getElementById('tokenMin').value;
      if (tokenMin !== '') params.set('tokenMin', tokenMin);
      if (onlyHdEl.checked) params.set('hd', 'true');
      return params;
    }

    function syncFavoritesToggleVisualState() {
      if (favoritesToggleBtn) favoritesToggleBtn.classList.toggle('is-active', !!onlyFavoritesEl.checked);
    }

    function setFiltersHidden(hidden) {
      filtersGrid.style.display = hidden ? 'none' : 'grid';
      toggleFiltersBtn.textContent = hidden ? 'Mostrar filtros' : 'Minimizar filtros';
    }

    // ---------- players ----------
    function iframeHtml(slot, index, forceRefresh) {
      return '<iframe class="player' + (videoOnlyEl.checked ? ' video-only' : '') + '" src="' +
        escapeHtml(buildPlayerSrc(slot.src, forceRefresh)) + '" title="Player ' + (index + 1) +
        '" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>';
    }

    function placeholderHtml() {
      return '<div class="player-placeholder">Escolha uma thumb na lista<br>para exibir neste player.</div>';
    }

    function renderPlayers() {
      playersGridEl.classList.toggle('has-max', maximizedSlot >= 0);
      playersGridEl.innerHTML = slots.map((slot, index) => {
        const isActive = index === activeSlot;
        const isMax = index === maximizedSlot;
        return `
          <article class="player-card ${isActive ? 'active-target' : ''} ${isMax ? 'maximized' : ''}" data-slot="${index}">
            <header class="player-bar" data-select-slot="${index}" role="button" tabindex="0" aria-label="Selecionar player ${index + 1}">
              <span class="slot-num">${index + 1}</span>
              <span class="slot-name">${slot.username ? escapeHtml(slot.username) : 'vazio'}</span>
              <span class="slot-flag" data-flag="${index}"></span>
              <span class="slot-tools">
                <button type="button" class="tool" data-act="reload" title="Recarregar">&#8635;</button>
                <button type="button" class="tool" data-act="max" title="Ampliar / reduzir (duplo clique na barra)">&#10530;</button>
                <button type="button" class="tool" data-act="fullscreen" title="Tela cheia">&#9974;&#xFE0E;</button>
                <a class="tool" data-act="open" title="Abrir no site" target="_blank" rel="noopener noreferrer" href="${slot.username ? 'https://chaturbate.com/' + encodeURIComponent(slot.username) + '/' : '#'}">&#8599;</a>
                <button type="button" class="tool" data-act="clear" title="Esvaziar">&#10005;</button>
              </span>
            </header>
            <div class="player-stage">${slot.src ? iframeHtml(slot, index, false) : placeholderHtml()}</div>
          </article>`;
      }).join('');
      updateSlotFlags();
    }

    function setActiveSlot(i) {
      activeSlot = Math.min(SLOT_COUNT - 1, Math.max(0, Number(i || 0)));
      playersGridEl.querySelectorAll('.player-card').forEach((card, idx) => {
        card.classList.toggle('active-target', idx === activeSlot);
      });
      saveState();
    }

    function updateSlotFrame(index, forceRefresh) {
      const slot = slots[index];
      const card = playersGridEl.querySelector('[data-slot="' + index + '"]');
      const stage = card && card.querySelector('.player-stage');
      if (!slot || !stage) return;

      const name = card.querySelector('.slot-name');
      if (name) name.textContent = slot.username || 'vazio';
      const open = card.querySelector('[data-act="open"]');
      if (open) open.href = slot.username ? 'https://chaturbate.com/' + encodeURIComponent(slot.username) + '/' : '#';

      if (!slot.src) {
        stage.innerHTML = placeholderHtml();
        return;
      }
      const iframe = stage.querySelector('iframe.player');
      if (iframe) {
        iframe.classList.toggle('video-only', videoOnlyEl.checked);
        iframe.src = buildPlayerSrc(slot.src, forceRefresh);
      } else {
        stage.innerHTML = iframeHtml(slot, index, forceRefresh);
      }
    }

    function refreshAllFrames() {
      for (let i = 0; i < SLOT_COUNT; i += 1) updateSlotFrame(i, true);
    }

    // Marca nos players os perfis que sairam da lista online.
    function updateSlotFlags() {
      slots.forEach((slot, i) => {
        const flag = playersGridEl.querySelector('[data-flag="' + i + '"]');
        if (!flag) return;
        if (!slot.username || !currentModels.length) { flag.textContent = ''; return; }
        const m = currentModels.find((x) => String(x.username).toLowerCase() === slot.username.toLowerCase());
        flag.textContent = m ? (m.num_users || 0) + ' views' : 'fora da lista';
        flag.classList.toggle('warn', !m);
      });
    }

    function nextEmptySlotAfter(index) {
      for (let step = 1; step <= SLOT_COUNT; step += 1) {
        const i = (index + step) % SLOT_COUNT;
        if (!slots[i].src) return i;
      }
      return (index + 1) % SLOT_COUNT;
    }

    function selectModelToActiveSlot(username) {
      const model = currentModels.find((m) => String(m.username || '').toLowerCase() === String(username || '').toLowerCase());
      if (!model || !model.embed_src) return;

      // Evita o mesmo perfil em dois players: se ja esta aberto, apenas foca nele.
      const existing = slots.findIndex((s) => s.username.toLowerCase() === model.username.toLowerCase());
      if (existing >= 0) {
        setActiveSlot(existing);
        statusEl.textContent = model.username + ' ja esta no player ' + (existing + 1);
        markListActive();
        return;
      }

      slots[activeSlot] = { username: model.username, src: model.embed_src };
      updateSlotFrame(activeSlot, false);
      const used = activeSlot;
      // Avanca automaticamente para o proximo player vazio.
      setActiveSlot(nextEmptySlotAfter(used));
      updateSlotFlags();
      markListActive();
      saveState();
      statusEl.textContent = model.username + ' no player ' + (used + 1) + ' • proximo: ' + (activeSlot + 1);
    }

    function clearSlot(index) {
      slots[index] = { username: '', src: '' };
      updateSlotFrame(index, false);
      setActiveSlot(index);
      updateSlotFlags();
      markListActive();
      saveState();
    }

    function toggleMaximize(index) {
      maximizedSlot = maximizedSlot === index ? -1 : index;
      playersGridEl.classList.toggle('has-max', maximizedSlot >= 0);
      playersGridEl.querySelectorAll('.player-card').forEach((card, i) => {
        card.classList.toggle('maximized', i === maximizedSlot);
      });
    }

    function markListActive() {
      const open = new Set(slots.map((s) => s.username.toLowerCase()).filter(Boolean));
      listEl.querySelectorAll('.item').forEach((item) => {
        item.classList.toggle('active', open.has(item.dataset.username));
      });
    }

    // ---------- lista ----------
    function applyNameFilter(models) {
      const q = nameSearchEl.value.trim().toLowerCase();
      if (!q) return models;
      return models.filter((m) => String(m.username || '').toLowerCase().includes(q) ||
        String(m.display_name || '').toLowerCase().includes(q));
    }

    function renderList(force) {
      const models = applyNameFilter(currentModels);
      // Thumbs renovadas a cada 60s; evita reconstruir o DOM quando nada mudou.
      const thumbBucket = Math.floor(Date.now() / 60000);
      const signature = thumbBucket + '|' + models.map((m) =>
        [m.username, m.num_users, m.current_show, m.is_hd ? 1 : 0].join(':')).join(',');
      if (!force && signature === lastListSignature) return;
      lastListSignature = signature;

      if (!models.length) {
        listEl.innerHTML = '<div class="empty">' + (currentModels.length
          ? 'Nenhum perfil corresponde a busca.'
          : 'Nenhuma modelo online com esses filtros agora.') + '</div>';
        return;
      }

      listEl.innerHTML = models.map((m) => {
        const usernameLower = String(m.username || '').toLowerCase();
        const thumb = String(m.image_url || '').trim();
        const thumbUrl = thumb ? thumb + (thumb.includes('?') ? '&' : '?') + 't=' + thumbBucket : '';
        const show = String(m.current_show || '').toLowerCase();
        const badges = [
          m.is_hd ? '<span class="badge badge-hd">HD</span>' : '',
          show && show !== 'public' ? '<span class="badge badge-show">' + escapeHtml(show) + '</span>' : '',
          m.is_new ? '<span class="badge badge-new">NEW</span>' : ''
        ].join('');
        return `
          <article class="item" data-username="${escapeHtml(usernameLower)}" tabindex="0" role="button" aria-label="Assistir ${escapeHtml(m.username)}">
            <div class="thumb-wrap">
              ${thumbUrl ? `<img class="thumb" src="${escapeHtml(thumbUrl)}" alt="" loading="lazy" decoding="async">` : '<div class="thumb"></div>'}
              <div class="badges">${badges}</div>
              <div class="views">${escapeHtml(formatViews(m.num_users))}</div>
            </div>
            <div class="meta">
              <div class="name" title="${escapeHtml(m.room_subject || m.username)}">${escapeHtml(m.username)}</div>
              <div class="time">${escapeHtml(secondsToHuman(m.seconds_online))} online</div>
            </div>
          </article>`;
      }).join('');
      markListActive();
    }

    // ---------- eventos ----------
    playersGridEl.addEventListener('click', (event) => {
      const tool = event.target.closest('[data-act]');
      const card = event.target.closest('.player-card');
      if (tool && card) {
        const index = Number(card.dataset.slot);
        const act = tool.dataset.act;
        if (act === 'open') return; // link normal
        event.preventDefault();
        if (act === 'reload') updateSlotFrame(index, true);
        else if (act === 'max') toggleMaximize(index);
        else if (act === 'clear') clearSlot(index);
        else if (act === 'fullscreen') {
          const stage = card.querySelector('.player-stage');
          if (stage && stage.requestFullscreen) stage.requestFullscreen().catch(() => {});
        }
        return;
      }
      const bar = event.target.closest('[data-select-slot]');
      if (bar) {
        setActiveSlot(Number(bar.dataset.selectSlot));
        statusEl.textContent = 'Player ' + (activeSlot + 1) + ' selecionado • clique em uma thumb';
      }
    });

    playersGridEl.addEventListener('dblclick', (event) => {
      const bar = event.target.closest('.player-bar');
      if (bar && !event.target.closest('.slot-tools')) {
        toggleMaximize(Number(bar.dataset.selectSlot));
      }
    });

    playersGridEl.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      const bar = event.target.closest('.player-bar');
      if (!bar || event.target !== bar) return;
      event.preventDefault();
      setActiveSlot(Number(bar.dataset.selectSlot));
    });

    document.addEventListener('keydown', (event) => {
      const tag = (event.target.tagName || '').toLowerCase();
      if (tag === 'input' || tag === 'select' || tag === 'textarea' || event.ctrlKey || event.metaKey || event.altKey) return;
      if (event.key >= '1' && event.key <= String(SLOT_COUNT)) {
        setActiveSlot(Number(event.key) - 1);
        statusEl.textContent = 'Player ' + event.key + ' selecionado • clique em uma thumb';
      }
    });

    refreshPlayersBtn.addEventListener('click', () => {
      refreshAllFrames();
      statusEl.textContent = 'Players recarregados • ativo: ' + (activeSlot + 1);
    });

    listEl.addEventListener('click', (event) => {
      const item = event.target.closest('.item');
      if (item) selectModelToActiveSlot(item.dataset.username || '');
    });

    listEl.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      const item = event.target.closest('.item');
      if (!item) return;
      event.preventDefault();
      selectModelToActiveSlot(item.dataset.username || '');
    });

    nameSearchEl.addEventListener('input', () => renderList(true));

    filtersForm.addEventListener('submit', (event) => {
      event.preventDefault();
      load();
    });

    clearFiltersBtn.addEventListener('click', () => {
      filtersForm.reset();
      document.getElementById('maxAge').value = '99';
      onlyFavoritesEl.checked = false;
      nameSearchEl.value = '';
      syncFavoritesToggleVisualState();
      saveState();
      load();
    });

    onlyFavoritesEl.addEventListener('change', () => {
      syncFavoritesToggleVisualState();
      saveState();
      load();
    });

    onlyHdEl.addEventListener('change', () => { saveState(); load(); });

    videoOnlyEl.addEventListener('change', () => {
      saveState();
      refreshAllFrames();
    });

    toggleFiltersBtn.addEventListener('click', () => {
      setFiltersHidden(filtersGrid.style.display !== 'none');
      saveState();
    });

    // ---------- carga de dados ----------
    async function fetchJson(url) {
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    }

    async function load() {
      if (loading) return;
      loading = true;
      try {
        let models = [];
        let modeLabel = 'busca';
        let dataAge = null;

        if (onlyFavoritesEl.checked) {
          const data = await fetchJson(API_FAVORITES_URL + '?_=' + Date.now());
          if (!data.ok) throw new Error(data.error || 'Erro ao carregar favoritos');
          models = Array.isArray(data.models) ? data.models : [];
          dataAge = data.data_age_seconds;
          modeLabel = 'favoritos';
        } else {
          const params = buildQueryFromFilters();
          params.set('_', Date.now().toString());
          const data = await fetchJson(API_SEARCH_URL + '?' + params.toString());
          if (!data.ok) throw new Error(data.error || 'Erro ao carregar dados');
          models = Array.isArray(data.results) ? data.results : [];
          dataAge = data.data_age_seconds;
        }

        errorEl.style.display = 'none';
        currentModels = models;
        renderList(false);
        updateSlotFlags();

        const stale = dataAge !== null && dataAge !== undefined && dataAge > 300;
        statusEl.textContent = models.length + ' online (' + modeLabel + ') • player ativo: ' +
          (activeSlot + 1) + ' • ' + new Date().toLocaleTimeString('pt-BR') +
          (stale ? ' • ⚠ dados com ' + secondsToHuman(dataAge) + ' (falha ao atualizar)' : '');
        statusEl.classList.toggle('stale', stale);
      } catch (err) {
        errorEl.textContent = 'Falha ao carregar: ' + err.message;
        errorEl.style.display = 'block';
        statusEl.textContent = 'Erro na atualizacao (nova tentativa automatica)';
      } finally {
        loading = false;
      }
    }

    function startPolling() {
      clearInterval(loadTimer);
      loadTimer = setInterval(load, REFRESH_MS);
    }

    // Pausa a atualizacao com a aba oculta e retoma ao voltar.
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        clearInterval(loadTimer);
      } else {
        load();
        startPolling();
      }
    });

    restoreState();
    renderPlayers();
    syncFavoritesToggleVisualState();
    load();
    startPolling();
  </script>
</body>
</html>
