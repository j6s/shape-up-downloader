<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use function Safe\parse_url;

class UrlConverter
{
    public function __construct(
        private RegexService $regex
    ) {
    }

    public function urlToInternal(string $url): string
    {
        /** @var string $path */
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');

        return $this->regex->replace('/\W+/', '-', $path);
    }
}
