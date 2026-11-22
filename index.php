<?php
declare(strict_types=1);

// Pagina unica do PAGECHAT: so marcacao. Logica em assets/js, estilo em assets/css, dados em api/.
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PAGECHAT</title>
  <link rel="preconnect" href="https://chaturbate.com">
  <link rel="preconnect" href="https://thumb.live.mmcdn.com">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/players.css">
  <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>
  <main class="layout">
    <section class="col-main">
      <div id="players-grid" class="players-grid"></div>
    </section>

    <aside id="sidebar" class="col-side">
      <div id="status" class="status" role="status" aria-live="polite">Carregando...</div>
      <div id="error" class="error" role="alert" hidden></div>

      <div class="toolbar">
        <div class="segmented" role="group" aria-label="Quantidade de players">
          <button type="button" class="btn" data-layout="1" title="1 player">1</button>
          <button type="button" class="btn" data-layout="2" title="2 players">2</button>
          <button type="button" class="btn" data-layout="4" title="4 players">4</button>
        </div>
        <button type="button" class="btn" data-fill title="Preenche players vazios com favoritos online">Favoritos nos players</button>
        <button type="button" class="btn" data-reload title="Recarrega todos os players">Recarregar</button>
      </div>

      <form id="filters-form" class="filters-panel" autocomplete="off">
        <input name="q" type="search" class="control" placeholder="Buscar perfil pelo nome..." aria-label="Buscar perfil">

        <div class="row">
          <label class="chip"><input type="checkbox" name="favorites">Somente favoritos</label>
          <label class="chip"><input type="checkbox" name="hd">Somente HD</label>
        </div>

        <button type="button" class="btn btn-link" data-toggle-filters>Ocultar filtros</button>

        <div class="filters-grid">
          <div class="row">
            <label class="field">Ordenacao
              <select name="sort" class="control">
                <option value="favorites">Favoritos primeiro</option>
                <option value="viewers">Mais views</option>
                <option value="recent">Mais recentes</option>
              </select>
            </label>
            <label class="field">Tag
              <input name="tag" type="text" class="control" placeholder="ex: new">
            </label>
          </div>

          <div class="field">Regiao
            <div class="row wrap">
              <label class="chip"><input type="checkbox" name="region" value="northamerica">NA</label>
              <label class="chip"><input type="checkbox" name="region" value="southamerica">SA</label>
              <label class="chip"><input type="checkbox" name="region" value="europe_russia">EU</label>
              <label class="chip"><input type="checkbox" name="region" value="asia">AS</label>
              <label class="chip"><input type="checkbox" name="region" value="other">OUT</label>
            </div>
          </div>

          <div class="row">
            <label class="field">Idade max
              <input name="maxAge" type="number" min="18" max="99" class="control" placeholder="99">
            </label>
            <label class="field">Show
              <select name="current_show" class="control">
                <option value="">Todos</option>
                <option value="public">Public</option>
                <option value="private">Private</option>
                <option value="group">Group</option>
                <option value="away">Away</option>
              </select>
            </label>
          </div>

          <div class="row">
            <label class="field">Novas
              <select name="is_new" class="control">
                <option value="">Todas</option>
                <option value="true">Somente novas</option>
                <option value="false">Nao novas</option>
              </select>
            </label>
            <label class="field">Token min
              <input name="tokenMin" type="number" min="0" class="control" placeholder="0">
            </label>
          </div>

          <label class="chip" title="Pede ao embed so o video (sem chat). Se o player ficar em branco, desmarque.">
            <input type="checkbox" data-video-only>Player sem recorte (experimental)
          </label>

          <button type="button" class="btn" data-clear>Limpar filtros</button>
        </div>
      </form>

      <div id="list" class="list"></div>
    </aside>
  </main>

  <script type="module" src="assets/js/main.js"></script>
</body>
</html>
