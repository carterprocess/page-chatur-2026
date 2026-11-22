<?php
declare(strict_types=1);

namespace Pagechat\Model;

/** Converte um registro bruto da API de afiliados no formato usado pela aplicacao. */
final class ModelNormalizer
{
    /** @return array<string,mixed>|null null quando o registro nao e reproduzivel */
    public function normalize(array $raw, string $region): ?array
    {
        if (empty($raw['username'])) {
            return null;
        }
        if (strtolower((string)($raw['current_show'] ?? '')) === 'offline') {
            return null;
        }

        $embedSrc = $this->extractIframeSrc((string)($raw['iframe_embed_revshare'] ?? ''));
        if ($embedSrc === '') {
            return null;
        }

        $subject = (string)($raw['room_subject'] ?? '');

        return [
            'username'         => (string)$raw['username'],
            'display_name'     => (string)($raw['display_name'] ?? '') ?: (string)$raw['username'],
            'image_url'        => (string)($raw['image_url_360x270'] ?? ($raw['image_url'] ?? '')),
            'num_users'        => (int)($raw['num_users'] ?? 0),
            'room_subject'     => $subject,
            'tokens_remaining' => $this->extractTokensRemaining($subject),
            'region'           => $region,
            'gender'           => (string)($raw['gender'] ?? ''),
            'is_hd'            => !empty($raw['is_hd']),
            'age'              => isset($raw['age']) ? (int)$raw['age'] : null,
            'tags'             => isset($raw['tags']) && is_array($raw['tags']) ? array_values($raw['tags']) : [],
            'current_show'     => (string)($raw['current_show'] ?? ''),
            'is_new'           => !empty($raw['is_new']),
            'seconds_online'   => (int)($raw['seconds_online'] ?? 0),
            'embed_src'        => $embedSrc,
            'room_url'         => (string)($raw['chat_room_url_revshare'] ?? ''),
            'is_favorite'      => false,
        ];
    }

    private function extractIframeSrc(string $html): string
    {
        if ($html !== '' && preg_match('/src=["\']([^"\']+)["\']/i', $html, $m)) {
            return $this->directEmbedUrl(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        return '';
    }

    /**
     * A URL de afiliado (/in/?...) passa por 2 redirecionamentos ate /embed/<sala>/. Usar o destino
     * final evita 3 requisicoes por player (rajadas de 4 players eram bloqueadas pelo servidor).
     */
    private function directEmbedUrl(string $src): string
    {
        $query = [];
        parse_str((string)parse_url($src, PHP_URL_QUERY), $query);
        $room = (string)($query['room'] ?? '');
        if ($room === '' || !preg_match('/^[A-Za-z0-9_]+$/', $room)) {
            return $src; // formato inesperado: mantem a URL original
        }

        $params = ['bgcolor' => $query['bgcolor'] ?? 'white'];
        foreach (['campaign', 'tour', 'track'] as $key) {
            if (isset($query[$key])) {
                $params[$key] = $query[$key];
            }
        }
        $params += ['disable_sound' => '0', 'join_overlay' => '1', 'room' => $room];
        ksort($params);

        return 'https://chaturbate.com/embed/' . $room . '/?' . http_build_query($params);
    }

    private function extractTokensRemaining(string $subject): ?int
    {
        if (preg_match('/\[(\d+)\s+tokens?\s+(?:remaining|left)\]/i', $subject, $m)) {
            return (int)$m[1];
        }
        return null;
    }
}
