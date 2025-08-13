<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\ContentModifier;

use J6s\ShapeUpDownloader\Exceptions\ContentExtractionException;
use J6s\ShapeUpDownloader\Exceptions\ModificationException;
use J6s\ShapeUpDownloader\Service\UrlConverter;
use Safe\Exceptions\UrlException;
use Symfony\Component\DomCrawler\Crawler;

class ReplaceLinksWithInternalLinksModifier implements PageContentModifier
{
    public function __construct(
        private UrlConverter $urlConverter
    ) {
    }

    public function modify(Crawler $document, array $urls): Crawler
    {
        try {
            $links = $document->filter('a')->links();
        } catch (\LogicException $e) {
            throw new ModificationException(
                sprintf('Cannot extract links from the document: %s', $e->getMessage()),
                previous: $e,
            );
        }

        foreach ($links as $link) {
            $uri = trim($link->getUri(), '/');

            $index = strpos($uri, '#');
            if (in_array($uri, $urls, true)) {
                // If it is a link to another page in this document
                try {
                    $uri = '#' . $this->urlConverter->urlToInternal($uri);
                } catch (ContentExtractionException $e) {
                    throw new ModificationException(
                        sprintf('Cannot convert URL "%s" to an internal hash-based link: %s', $uri, $e->getMessage()),
                        previous: $e,
                    );
                }
            } elseif ($index !== false) {
                // If it already is a hash-based link
                $uri = substr($uri, $index);
            }

            $link->getNode()->setAttribute('href', $uri);
        }

        return $document;
    }
}
