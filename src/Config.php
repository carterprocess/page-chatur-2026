<?php
declare(strict_types=1);

namespace Pagechat;

final class Config
{
    private array $values;
    private string $root;

    public function __construct(string $root, array $values)
    {
        $this->root = rtrim($root, '/\\');
        $this->values = $values;
    }

    public static function load(string $root): self
    {
        $values = require $root . '/config/app.php';
        return new self($root, is_array($values) ? $values : []);
    }

    public function get(string $key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    /** Caminho absoluto a partir de uma chave de configuracao relativa a raiz. */
    public function path(string $key): string
    {
        return $this->root . '/' . ltrim((string)$this->get($key, ''), '/\\');
    }
}
