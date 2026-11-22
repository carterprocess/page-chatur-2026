<?php
declare(strict_types=1);

// GET /api/models.php  -> modelos online (filtrados, ordenados), com cache renovado sob demanda.
// Parametros: ver Pagechat\Model\ModelQuery.

require __DIR__ . '/../src/autoload.php';

use Pagechat\Cache\CacheRefresher;
use Pagechat\Cache\RegionCache;
use Pagechat\Config;
use Pagechat\Http\Client;
use Pagechat\Http\JsonResponse;
use Pagechat\Model\Favorites;
use Pagechat\Model\ModelNormalizer;
use Pagechat\Model\ModelQuery;
use Pagechat\Model\ModelRepository;

try {
    $config = Config::load(dirname(__DIR__));
    $query = new ModelQuery($_GET);
    $regions = $query->regions($config->get('regions', []));

    $cache = new RegionCache($config->path('cache_dir'));
    (new CacheRefresher(
        $cache,
        new Client((int)$config->get('fetch_timeout', 10)),
        (string)$config->get('api_url'),
        (string)$config->get('affiliate_id', ''),
        (int)$config->get('cache_ttl', 60)
    ))->refresh($regions);

    $favorites = (new Favorites($config->path('favorites_file')))->all();
    $models = (new ModelRepository($cache, new ModelNormalizer()))->online($regions);
    [$results, $total] = $query->apply($models, $favorites);

    JsonResponse::send([
        'ok'               => true,
        'updated_at'       => date('c'),
        'data_age_seconds' => $cache->oldestAge($regions),
        'favorites_total'  => count($favorites),
        'count'            => $total,
        'results'          => $results,
    ]);
} catch (Throwable $e) {
    error_log('[PAGECHAT] ' . $e->getMessage());
    JsonResponse::send(['ok' => false, 'error' => 'Falha interna ao carregar modelos.', 'results' => []], 500);
}
