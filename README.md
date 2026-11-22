# PAGECHAT

Painel multi-player para acompanhar perfis do Chaturbate: 4 players simultaneos, lista de perfis online (favoritos ou busca com filtros) e atualizacao automatica.

## Estrutura

- `index.php`: interface (4 players + painel lateral).
- `styles.css`: estilos.
- `api-online-models.php`: perfis de `followed-models.txt` que estao online.
- `api-search-models.php`: busca com filtros (regiao, idade, tag, show, novas, tokens).
- `followed-models.txt`: lista de perfis (um por linha), sincronizada com a planilha (aba `CHAT`, coluna `PERFIL`).
- `Chaturbate-affiliate-api-script/`: biblioteca que mantem o cache `cache/cams_<regiao>.json`.

## Regras

- Toda mudanca deve terminar com `git commit` e `git push`.
