<?php declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use Symfony\Component\DomCrawler\Crawler;

class TableOfContentsExtractor
{
    public function __construct(
        private UrlConverter $urlConverter
    ) { }

    public function extractTableOfContentsHtml(Crawler $document, array $urls): string
    {
        $this->replaceLinksWithInternalOnes($document, $urls);
        return $document->filter('.toc')->html();
    }


    /** @return string[] */
    public function extractChapterUrls(Crawler $document, string $baseUrl): array
    {
        $elements = $document->filter('.toc__chapter-title a');
        $links = array_filter(
            iterator_to_array($elements),
            fn ($element) => $element instanceof \DOMElement
        );

        return array_map(
            fn(\DOMElement $element) =>  $baseUrl . $element->getAttribute('href'),
            $links
        );
    }

    private function replaceLinksWithInternalOnes(Crawler $document, array $urls): void
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
    }

}
