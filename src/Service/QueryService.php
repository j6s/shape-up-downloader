<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\DomCrawler\Crawler;

class QueryService
{
    public function __construct(
        private AbstractAdapter $cache,
        private RegexService $regex
    ) {
    }

    public function getDocument(string $url): Crawler
    {
        return new Crawler(
            $this->cachedRequest($url),
            $url
        );
    }

    public function cachedRequest(string $url): string
    {
        $key = $this->regex->replace('/\W/', '-', $url);
        return (string)$this->cache->get($key, function () use ($url) {
            return file_get_contents($url);
        });
    }
}
