<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use J6s\ShapeUpDownloader\Exceptions\ContentExtractionException;
use Safe\Exceptions\PcreException;
use Safe\Exceptions\UrlException;

use function Safe\parse_url;
use function Safe\preg_replace;

class UrlConverter
{
    /**
     * @throws ContentExtractionException
     */
    public function urlToInternal(string $url): string
    {
        try {
            $path = parse_url($url, PHP_URL_PATH);
        } catch (UrlException $e) {
            throw new ContentExtractionException(
                sprintf('Cannot extract path from URL "%s": %s', $url, $e->getMessage()),
                previous: $e
            );
        }

        if (!is_string($path)) {
            throw new ContentExtractionException(
                sprintf('Cannot extract path from URL "%s": no path found', $url)
            );
        }

        $path = trim($path, '/');

        try {
            $path = preg_replace('/\W+/', '-', $path);
        } catch (PcreException $e) {
            throw new ContentExtractionException(
                sprintf('Cannot convert URL "%s" to an internal link: %s', $url, $e->getMessage()),
                previous: $e
            );
        }

        return $path;
    }
}
