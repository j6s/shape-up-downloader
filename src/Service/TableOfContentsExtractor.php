<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use DOMElement;
use J6s\ShapeUpDownloader\Exceptions\ContentExtractionException;
use Symfony\Component\DomCrawler\Crawler;

class TableOfContentsExtractor
{
    /**
     * @throws ContentExtractionException
     */
    public function extractTableOfContentsHtml(Crawler $document): string
    {
        try {
            return $document->filter('.toc')->html();
        } catch (\LogicException $e) {
            throw new ContentExtractionException(sprintf(
                'Cannot extract table of contents HTML from the document: %s',
                $e->getMessage(),
            ), previous: $e);
        }
    }


    /**
     * @return list<string>
     * @throws ContentExtractionException
     */
    public function extractChapterUrls(Crawler $document, string $baseUrl): array
    {
        try {
            $elements = $document->filter('.toc__chapter-title a');
        } catch (\LogicException $e) {
            throw new ContentExtractionException(sprintf(
                'Cannot extract chapter URLs from the document: %s',
                $e->getMessage(),
            ), previous: $e);
        }

        $links = array_values(array_filter(
            iterator_to_array($elements),
            fn($element) => $element instanceof DOMElement
        ));

        return array_map(
            fn(DOMElement $element) => $baseUrl . $element->getAttribute('href'),
            $links
        );
    }
}
