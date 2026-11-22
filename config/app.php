<?php
// Configuracao da aplicacao (sem segredos).
return [
    'affiliate_id'   => '2DLMP',
    'regions'        => ['northamerica', 'europe_russia', 'southamerica', 'asia', 'other'],
    'cache_ttl'      => 60,   // segundos ate renovar o cache de uma regiao
    'fetch_timeout'  => 10,   // timeout por requisicao a API de afiliados
    'api_url'        => 'https://chaturbate.com/api/public/affiliates/onlinerooms/',
    'favorites_file' => 'data/followed-models.txt',
    'cache_dir'      => 'var/cache',
];
