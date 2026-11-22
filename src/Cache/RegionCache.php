<?php
declare(strict_types=1);

namespace Pagechat\Cache;

/** Arquivos var/cache/cams_<regiao>.json: leitura, escrita atomica e idade. */
final class RegionCache
{
    public function __construct(private string $dir)
    {
    }

    public function file(string $region): string
    {
        return $this->dir . '/cams_' . $region . '.json';
    }

    public function ensureDir(): void
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    public function lockPath(): string
    {
        return $this->dir . '/.refresh.lock';
    }

    /** @return array<int,array> resultados brutos da API para a regiao */
    public function read(string $region): array
    {
        $file = $this->file($region);
        if (!is_file($file)) {
            return [];
        }
        $json = json_decode((string)file_get_contents($file), true);
        return is_array($json) && isset($json['results']) && is_array($json['results']) ? $json['results'] : [];
    }

    public function write(string $region, array $payload): void
    {
        $this->ensureDir();
        $file = $this->file($region);
        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
            @rename($tmp, $file);
        }
    }

    public function age(string $region): ?int
    {
        $file = $this->file($region);
        return is_file($file) ? max(0, time() - (int)filemtime($file)) : null;
    }

    /** Idade do cache mais antigo entre as regioes (null se nenhum existe). */
    public function oldestAge(array $regions): ?int
    {
        $oldest = null;
        foreach ($regions as $region) {
            $age = $this->age($region);
            if ($age !== null) {
                $oldest = $oldest === null ? $age : max($oldest, $age);
            }
        }
        return $oldest;
    }
}
