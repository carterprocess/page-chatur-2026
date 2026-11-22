<?php
// Atualiza (sob demanda) os caches cams_<regiao>.json a partir da API publica de afiliados.
// Usado por api-search-models.php e api-online-models.php; nao requer cron.

const CACHE_TTL_SECONDS = 60;
const CACHE_FETCH_TIMEOUT = 10;

function refresh_models_cache(array $regions, $cacheDir, $ttl = CACHE_TTL_SECONDS)
{
    $config = @include __DIR__ . '/Chaturbate-affiliate-api-script/config.php';
    $affiliateId = is_array($config) && !empty($config['affiliate_id']) ? $config['affiliate_id'] : '';
    if ($affiliateId === '') {
        return;
    }

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    $stale = [];
    foreach ($regions as $region) {
        $file = $cacheDir . '/cams_' . $region . '.json';
        if (!file_exists($file) || (time() - (int)@filemtime($file)) >= $ttl) {
            $stale[] = $region;
        }
    }
    if (count($stale) === 0) {
        return;
    }

    // Apenas uma requisicao por vez atualiza; as demais usam o cache existente.
    $lock = @fopen($cacheDir . '/.refresh.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return;
    }

    try {
        $urls = [];
        foreach ($stale as $region) {
            $urls[$region] = 'https://chaturbate.com/api/public/affiliates/onlinerooms/?wm=' . urlencode($affiliateId) .
                '&client_ip=1.2.3.4&region=' . urlencode($region) . '&format=json&limit=500';
        }

        $bodies = function_exists('curl_multi_init') ? fetch_parallel($urls) : fetch_sequential($urls);

        foreach ($bodies as $region => $body) {
            $json = is_string($body) ? json_decode($body, true) : null;
            if (!is_array($json) || !isset($json['results']) || !is_array($json['results'])) {
                continue; // mantem o cache anterior em caso de falha
            }
            $json['cached_ts'] = time();
            $file = $cacheDir . '/cams_' . $region . '.json';
            $tmp = $file . '.tmp';
            if (@file_put_contents($tmp, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
                @rename($tmp, $file);
            }
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function fetch_parallel(array $urls)
{
    $mh = curl_multi_init();
    $handles = [];
    foreach ($urls as $key => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => CACHE_FETCH_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PAGECHAT)',
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running && $status === CURLM_OK);

    $out = [];
    foreach ($handles as $key => $ch) {
        $out[$key] = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 ? curl_multi_getcontent($ch) : null;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}

function fetch_sequential(array $urls)
{
    $ctx = stream_context_create(['http' => ['timeout' => CACHE_FETCH_TIMEOUT, 'user_agent' => 'Mozilla/5.0 (compatible; PAGECHAT)']]);
    $out = [];
    foreach ($urls as $key => $url) {
        $body = @file_get_contents($url, false, $ctx);
        $out[$key] = $body === false ? null : $body;
    }
    return $out;
}

// Idade (em segundos) do cache mais antigo entre as regioes informadas.
function cache_age_seconds(array $regions, $cacheDir)
{
    $oldest = null;
    foreach ($regions as $region) {
        $file = $cacheDir . '/cams_' . $region . '.json';
        if (!file_exists($file)) {
            continue;
        }
        $age = time() - (int)filemtime($file);
        $oldest = $oldest === null ? $age : max($oldest, $age);
    }
    return $oldest;
}
