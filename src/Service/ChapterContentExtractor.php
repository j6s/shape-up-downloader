<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use DOMElement;
use DOMNode;
use J6s\ShapeUpDownloader\Exceptions\ContentExtractionException;
use Safe\Exceptions\UrlException;
use Symfony\Component\DomCrawler\Crawler;

class ChapterContentExtractor
{
    public function __construct(
        private UrlConverter $urlConverter
    ) {
    }

    /**
     * @throws ContentExtractionException
     */
    public function extractTitle(Crawler $document): string
    {

        try {
            $chapterNumberElement = $document->filter('.intro__masthead');
            $chapterNumber = $chapterNumberElement->count() > 0 ? $chapterNumberElement->text() : '';

            $titleElement = $document->filter('.intro__title > a');
            $title = $titleElement->text();
        } catch (\LogicException | \RuntimeException $e) {
            throw new ContentExtractionException(
                sprintf('Cannot extract title from the document: %s', $e->getMessage()),
                previous: $e,
            );
        }

        return sprintf(
            '<h1 id="%s">%s %s</h1>',
            $this->urlConverter->urlToInternal((string)$document->getUri()),
            $chapterNumber,
            $title
        );
    }

    /**
     * @throws ContentExtractionException
     */
    public function extractBodyText(Crawler $document): string
    {
        $body = '';

        try {
            $children = $document->filter('.content')->children();
        } catch (\LogicException | \RuntimeException $e) {
            throw new ContentExtractionException(
                sprintf('Cannot extract body text from the document: %s', $e->getMessage()),
                previous: $e,
            );
        }

        foreach ($children as $child) {
            if (!($child instanceof DOMElement) || !($child->ownerDocument instanceof DOMNode)) {
                continue;
            }
            if ($child->tagName !== 'template' && $child->tagName !== 'footer' && $child->tagName !== 'nav') {
                $body .= $child->ownerDocument->saveXML($child);
            }
        }

        return $body;
    }
}
