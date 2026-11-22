<?php
declare(strict_types=1);

namespace Pagechat\Cache;

use Pagechat\Http\Client;

/** Renova sob demanda o cache das regioes vencidas, com trava para evitar disputa. */
final class CacheRefresher
{
    public function __construct(
        private RegionCache $cache,
        private Client $http,
        private string $apiUrl,
        private string $affiliateId,
        private int $ttl
    ) {
    }

    public function refresh(array $regions): void
    {
        if ($this->affiliateId === '') {
            return;
        }

        $stale = [];
        foreach ($regions as $region) {
            $age = $this->cache->age($region);
            if ($age === null || $age >= $this->ttl) {
                $stale[] = $region;
            }
        }
        if (!$stale) {
            return;
        }

        $this->cache->ensureDir();
        $lock = @fopen($this->cache->lockPath(), 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            return; // outra requisicao ja esta atualizando
        }

        try {
            $urls = [];
            foreach ($stale as $region) {
                $urls[$region] = $this->apiUrl . '?' . http_build_query([
                    'wm' => $this->affiliateId,
                    'client_ip' => '1.2.3.4',
                    'region' => $region,
                    'format' => 'json',
                    'limit' => 500,
                ]);
            }
            foreach ($this->http->fetchAll($urls) as $region => $body) {
                $json = is_string($body) ? json_decode($body, true) : null;
                if (is_array($json) && isset($json['results']) && is_array($json['results'])) {
                    $json['cached_ts'] = time();
                    $this->cache->write((string)$region, $json); // em falha, mantem o cache antigo
                }
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
