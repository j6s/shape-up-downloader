<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

class TableOfContentsExtractor
{
    public function __construct(
        private UrlConverter $urlConverter
    ) {
    }

    public function extractTableOfContentsHtml(Crawler $document, array $urls): string
    {
        return $document->filter('.toc')->html();
    }


    /** @return string[] */
    public function extractChapterUrls(Crawler $document, string $baseUrl): array
    {
        $elements = $document->filter('.toc__chapter-title a');
        $links = array_filter(
            iterator_to_array($elements),
            fn($element) => $element instanceof DOMElement
        );

        return array_map(
            fn(DOMElement $element) => $baseUrl . $element->getAttribute('href'),
            $links
        );
    }
}
