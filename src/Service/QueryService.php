<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use J6s\ShapeUpDownloader\Exceptions\RequestException;
use Psr\Cache\InvalidArgumentException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\PcreException;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\DomCrawler\Crawler;

use function Safe\preg_replace;
use function Safe\file_get_contents;

class QueryService
{
    public function __construct(
        private AbstractAdapter $cache,
    ) {
    }

    /**
     * @throws RequestException
     */
    public function getDocument(string $url): Crawler
    {
        return new Crawler(
            $this->cachedRequest($url),
            $url
        );
    }

    /**
     * @param string $url
     * @return string
     * @throws RequestException
     */
    public function cachedRequest(string $url): string
    {
        try {
            $key = preg_replace('/\W/', '-', $url);
        } catch (PcreException $e) {
            throw new RequestException(
                sprintf('Cannot convert URL "%s" to a cache key: %s', $url, $e->getMessage()),
                previous: $e,
            );
        }

        try {
            return (string)$this->cache->get($key, function () use ($url) {
                return file_get_contents($url);
            });
        } catch (InvalidArgumentException | FilesystemException $e) {
            throw new RequestException(
                sprintf('Failed to fetch content from URL "%s": %s', $url, $e->getMessage()),
                previous: $e,
            );
        }
    }
}
