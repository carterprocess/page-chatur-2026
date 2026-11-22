<?php
declare(strict_types=1);

namespace Pagechat\Model;

/** Criterios de busca vindos da query string: filtra, ordena e pagina modelos. */
final class ModelQuery
{
    private const SORTS = ['viewers', 'recent', 'favorites'];

    private array $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /** Regioes pedidas, restritas as validas (todas se nenhuma informada). */
    public function regions(array $valid): array
    {
        $wanted = array_values(array_intersect($this->list('region'), $valid));
        return $wanted ?: $valid;
    }

    public function onlyFavorites(): bool
    {
        return $this->bool('favorites') === true;
    }

    /**
     * @param array<int,array<string,mixed>> $models
     * @param array<string,true> $favorites
     * @return array{0:array,1:int} [pagina de resultados, total apos filtros]
     */
    public function apply(array $models, array $favorites): array
    {
        foreach ($models as &$m) {
            $m['is_favorite'] = isset($favorites[strtolower($m['username'])]);
        }
        unset($m);

        $models = array_values(array_filter($models, fn(array $m): bool => $this->matches($m)));
        usort($models, $this->comparator());

        $total = count($models);
        $limit = isset($this->params['limit']) ? max(1, (int)$this->params['limit']) : max(1, $total);
        $offset = max(0, (int)($this->params['offset'] ?? 0));
        return [array_slice($models, $offset, $limit), $total];
    }

    private function matches(array $m): bool
    {
        if ($this->onlyFavorites() && !$m['is_favorite']) {
            return false;
        }

        $genders = $this->list('gender');
        if ($genders && !in_array($m['gender'], $genders, true)) {
            return false;
        }

        $tags = array_map('strtolower', $this->list('tag'));
        if ($tags && !array_intersect($tags, array_map('strtolower', $m['tags']))) {
            return false;
        }

        $hd = $this->bool('hd');
        if ($hd !== null && $m['is_hd'] !== $hd) {
            return false;
        }

        $isNew = $this->bool('is_new');
        if ($isNew !== null && $m['is_new'] !== $isNew) {
            return false;
        }

        $shows = array_map('strtolower', $this->list('current_show'));
        if ($shows && !in_array(strtolower($m['current_show']), $shows, true)) {
            return false;
        }

        // Idade: so exclui perfis sem idade informada quando o filtro e restritivo.
        $minAge = $this->int('minAge');
        $maxAge = $this->int('maxAge');
        if (($minAge !== null && $minAge > 18) || ($maxAge !== null && $maxAge < 99)) {
            if ($m['age'] === null) {
                return false;
            }
            if ($minAge !== null && $m['age'] < $minAge) {
                return false;
            }
            if ($maxAge !== null && $m['age'] > $maxAge) {
                return false;
            }
        }

        $tokenMin = $this->int('tokenMin');
        $tokenMax = $this->int('tokenMax');
        if ($tokenMin !== null || $tokenMax !== null) {
            if ($m['tokens_remaining'] === null) {
                return false;
            }
            if ($tokenMin !== null && $m['tokens_remaining'] < $tokenMin) {
                return false;
            }
            if ($tokenMax !== null && $m['tokens_remaining'] > $tokenMax) {
                return false;
            }
        }

        $q = strtolower(trim((string)($this->params['q'] ?? '')));
        if ($q !== '' && strpos(strtolower($m['username'] . ' ' . $m['display_name']), $q) === false) {
            return false;
        }

        return true;
    }

    private function comparator(): callable
    {
        $sort = strtolower((string)($this->params['sort'] ?? 'viewers'));
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'viewers';

        return function (array $a, array $b) use ($sort): int {
            if ($sort === 'favorites' && $a['is_favorite'] !== $b['is_favorite']) {
                return $a['is_favorite'] ? -1 : 1;
            }
            if ($sort === 'recent') {
                return $a['seconds_online'] <=> $b['seconds_online'];
            }
            return $b['num_users'] <=> $a['num_users'];
        };
    }

    /** @return string[] valores (aceita array e lista separada por virgula) */
    private function list(string $key): array
    {
        if (!isset($this->params[$key])) {
            return [];
        }
        $values = is_array($this->params[$key]) ? $this->params[$key] : [$this->params[$key]];
        $out = [];
        foreach ($values as $entry) {
            foreach (explode(',', (string)$entry) as $item) {
                $item = trim($item);
                if ($item !== '') {
                    $out[] = $item;
                }
            }
        }
        return $out;
    }

    private function int(string $key): ?int
    {
        $v = $this->params[$key] ?? '';
        return is_scalar($v) && $v !== '' && is_numeric($v) ? (int)$v : null;
    }

    private function bool(string $key): ?bool
    {
        if (!isset($this->params[$key]) || $this->params[$key] === '') {
            return null;
        }
        return in_array(strtolower((string)$this->params[$key]), ['1', 'true', 'yes'], true);
    }
}
