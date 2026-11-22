<?php
declare(strict_types=1);

namespace Pagechat\Model;

use Pagechat\Cache\RegionCache;

/** Le o cache por regiao e devolve modelos normalizados, sem duplicados. */
final class ModelRepository
{
    public function __construct(private RegionCache $cache, private ModelNormalizer $normalizer)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function online(array $regions): array
    {
        $map = [];
        foreach ($regions as $region) {
            foreach ($this->cache->read($region) as $raw) {
                $model = is_array($raw) ? $this->normalizer->normalize($raw, $region) : null;
                if ($model !== null) {
                    $map[strtolower($model['username'])] = $model;
                }
            }
        }
        return array_values($map);
    }
}
