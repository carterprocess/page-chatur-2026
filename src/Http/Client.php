<?php
declare(strict_types=1);

namespace Pagechat\Http;

/** Cliente HTTP minimo: busca varias URLs em paralelo (curl_multi) ou em sequencia. */
final class Client
{
    private const USER_AGENT = 'Mozilla/5.0 (compatible; PAGECHAT)';

    public function __construct(private int $timeout = 10)
    {
    }

    /**
     * @param array<string,string> $urls chave => url
     * @return array<string,?string> chave => corpo (null em caso de falha)
     */
    public function fetchAll(array $urls): array
    {
        return function_exists('curl_multi_init') ? $this->parallel($urls) : $this->sequential($urls);
    }

    private function parallel(array $urls): array
    {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($urls as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_ENCODING       => '',
                CURLOPT_USERAGENT      => self::USER_AGENT,
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
            $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            $out[$key] = $ok ? (string)curl_multi_getcontent($ch) : null;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        return $out;
    }

    private function sequential(array $urls): array
    {
        $ctx = stream_context_create(['http' => ['timeout' => $this->timeout, 'user_agent' => self::USER_AGENT]]);
        $out = [];
        foreach ($urls as $key => $url) {
            $body = @file_get_contents($url, false, $ctx);
            $out[$key] = $body === false ? null : $body;
        }
        return $out;
    }
}
