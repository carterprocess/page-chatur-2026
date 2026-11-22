<?php
declare(strict_types=1);

namespace Pagechat\Model;

/** Lista fixa de perfis (um por linha; linhas vazias e "#" sao ignoradas). */
final class Favorites
{
    public function __construct(private string $file)
    {
    }

    /** @return array<string,true> usernames em minusculas */
    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $set = [];
        foreach (file($this->file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $name = strtolower(trim($line));
            if ($name !== '' && $name[0] !== '#') {
                $set[$name] = true;
            }
        }
        return $set;
    }
}
