# PAGECHAT

Painel multi-player para acompanhar perfis do Chaturbate: 1, 2 ou 4 players simultaneos, lista de modelos online (favoritos em destaque) e filtros com atualizacao automatica.

## Arquitetura

```
index.php            pagina unica: so marcacao (sem logica)
api/models.php       endpoint unico GET: filtra/ordena modelos online
config/app.php       configuracao (afiliado, regioes, TTL do cache)
data/                dados versionados (followed-models.txt = favoritos)
var/cache/           cache de runtime (ignorado pelo git)
src/                 backend PHP (PSR-4, namespace Pagechat\)
  Config.php           leitura da configuracao
  Http/                Client (curl_multi), JsonResponse
  Cache/               RegionCache (arquivos), CacheRefresher (renova sob demanda, TTL 60s, com trava)
  Model/               ModelNormalizer, ModelRepository, Favorites, ModelQuery (filtros/ordem)
assets/css/          base, players, sidebar
assets/js/           modulos ES (sem build)
  config.js            constantes e filtros padrao
  store.js             estado + persistencia (localStorage) + subscribe por chave
  actions.js           regras de negocio (selecionar, limpar, layout, favoritos)
  api.js               cliente do endpoint
  util.js              funcoes puras
  ui/                  players, list, filters, controls, status (cada um so cuida do seu DOM)
  main.js              composicao e polling
tests/               testes de logica pura: node --test tests/store-actions.test.mjs
```

Fluxo: `main.js` carrega modelos via `api.js` -> `store` -> modulos de `ui/` reagem so as chaves que mudaram. O cache `var/cache/cams_<regiao>.json` e renovado pela propria API quando vence (sem cron).

## Como evoluir

- Novo filtro: campo em `index.php` (`name`), `DEFAULT_FILTERS` e `ui/filters.js`; no servidor, regra em `ModelQuery::matches`.
- Nova origem de dados: implemente outra fonte e injete em `api/models.php` no lugar de `ModelRepository`.
- Novo componente de UI: um modulo em `assets/js/ui/` assinando `store.subscribe([...chaves], fn)`.

## Favoritos

`data/followed-models.txt` (um perfil por linha). Origem: planilha, aba `CHAT`, coluna `PERFIL`.

## Regras

- Toda mudanca deve terminar com `git commit` e `git push`.
- `.htaccess` bloqueia acesso web a `src/`, `config/`, `data/`, `var/` e `tests/`.
