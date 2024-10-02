<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ComicKingApi
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly TagAwareCacheInterface $cacheItem,
        private string $base
    ) {}

    public function getComicTitles(
        string $comicCode,
        ?array $langs = [],
        ?int $limit = null
    ): array {
        if (!$langs) {
            $langs = ['en'];
        }

        return $this->cacheItem->get(
            'api.comicking.comic_titles.' . $comicCode
                . ($langs ? '_' . \implode('', \array_map(function (string $val): string {
                    return \str_replace('-', '', $val);
                }, $langs)) : '')
                . ($limit ? '_' . $limit : ''),
            function (ItemInterface $item) use ($comicCode, $langs, $limit): mixed {
                $item->expiresAfter(28800);
                $item->tag(['comic', 'comicTitle']);

                $result = [];

                $page = 1;
                while (true) {
                    $url = $this->base . '/rest/comics/' . $comicCode . '/titles?page=' . $page;
                    if ($limit) $url .= '&limit=' . $limit;
                    $url .= '&orderBys[]=languageLang%20prefer=' . \implode('%2B', $langs);
                    $url .= '&orderBys[]=isSynonym%20nulls=last';
                    $url .= '&orderBys[]=isLatinized%20order=desc%20nulls=last';
                    $url .= '&orderBys[]=createdAt%20desc';

                    $response = $this->client->request(Request::METHOD_GET, $url);
                    \array_push($result, ...$response->toArray(true));

                    $count = \count($result);
                    if ($count >= $limit) break;
                    if ($count >= $response->getHeaders()['x-total-count'][0]) {
                        break;
                    }

                    $page += 1;
                }

                return $result;
            }
        );
    }

    public function getComicCovers(
        string $comicCode,
        ?array $hints = [],
        ?int $limit = null
    ): array {
        return $this->cacheItem->get(
            'api.comicking.comic_covers.' . $comicCode
                . ($hints ? '_' . \implode('', $hints) : '')
                . ($limit ? '_' . $limit : ''),
            function (ItemInterface $item) use ($comicCode, $hints, $limit): mixed {
                $item->expiresAfter(28800);
                $item->tag(['comic', 'comicCover']);

                $result = [];

                $page = 1;
                while (true) {
                    $url = $this->base . '/rest/comics/' . $comicCode . '/covers?page=' . $page;
                    if ($limit) $url .= '&limit=' . $limit;
                    $url .= '&orderBys[]=hint%20prefer=' . \implode('%2B', $hints);
                    $url .= '&orderBys[]=createdAt%20desc';

                    $response = $this->client->request(Request::METHOD_GET, $url);
                    array_push($result, ...$response->toArray(true));

                    $count = \count($result);
                    if ($count >= $limit) break;
                    if ($count >= $response->getHeaders()['x-total-count'][0]) {
                        break;
                    }

                    $page += 1;
                }

                return $result;
            }
        );
    }

    public function getComicSynopses(
        string $comicCode,
        ?array $sources = [],
        ?array $langs = [],
        ?int $limit = null
    ): array {
        if (!$langs) {
            $langs = ['en'];
        }

        return $this->cacheItem->get(
            'api.comicking.comic_synopses.' . $comicCode
                . ($sources ? '_' . \implode('', $sources) : '')
                . ($langs ? '_' . \implode('', \array_map(function (string $val): string {
                    return \str_replace('-', '', $val);
                }, $langs)) : '')
                . ($limit ? '_' . $limit : ''),
            function (ItemInterface $item) use ($comicCode, $sources, $langs, $limit): mixed {
                $item->expiresAfter(28800);
                $item->tag(['comic', 'comicSynopsis']);

                $result = [];

                $page = 1;
                while (true) {
                    $url = $this->base . '/rest/comics/' . $comicCode . '/synopses?page=' . $page;
                    if ($limit) $url .= '&limit=' . $limit;
                    $url .= '&orderBys[]=source%20prefer=' . \implode('%2B', $sources);
                    $url .= '&orderBys[]=languageLang%20prefer=' . \implode('%2B', $langs);
                    $url .= '&orderBys[]=createdAt%20desc';

                    $response = $this->client->request(Request::METHOD_GET, $url);
                    array_push($result, ...$response->toArray(true));

                    $count = \count($result);
                    if ($count >= $limit) break;
                    if ($count >= $response->getHeaders()['x-total-count'][0]) {
                        break;
                    }

                    $page += 1;
                }

                return $result;
            }
        );
    }
}
