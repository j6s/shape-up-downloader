<?php declare(strict_types=1);

namespace J6s\ShapeUpDownloader\ContentModifier;

use J6s\ShapeUpDownloader\Service\UrlConverter;
use Symfony\Component\DomCrawler\Crawler;
use function Safe\parse_url;
use function Safe\preg_replace;

class ReplaceLinksWithInternalLinksModifier implements PageContentModifier
{
    public function __construct(
        private UrlConverter $urlConverter
    ) { }

    public function modify(Crawler $document, array $urls): Crawler
    {
        foreach ($document->filter('a')->links() as $link) {
            $uri = trim($link->getUri(), '/');

            $index = strpos($uri, '#');
            if (in_array($uri, $urls)) {
                // If it is a link to another page in this document
                $uri = '#' . $this->urlConverter->urlToInternal($uri);
            } elseif ($index !== false) {
                // If it already is a hash-based link
                $uri = substr($uri, $index);
            }


            $link->getNode()->setAttribute('href', $uri);
        }

        return $document;
    }
}
