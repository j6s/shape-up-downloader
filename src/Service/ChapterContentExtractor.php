<?php declare(strict_types=1);


namespace J6s\ShapeUpDownloader\Service;


use Symfony\Component\DomCrawler\Crawler;

class ChapterContentExtractor
{

    public function __construct(
        private UrlConverter $urlConverter
    ) { }

    public function extractTitle(Crawler $document): string
    {
        $chapterNumberElement = $document->filter('.intro__masthead');
        $chapterNumber = $chapterNumberElement->count() > 0 ? $chapterNumberElement->text() : '';

        $titleElement = $document->filter('.intro__title > a');
        $title = $titleElement->text();

        return sprintf(
            '<h1 id="%s">%s %s</h1>',
            $this->urlConverter->urlToInternal((string) $document->getUri()),
            $chapterNumber,
            $title
        );
    }


    public function extractBodyText(Crawler $document): string
    {
        $body = '';

        foreach ($document->filter('.content')->children() as $child) {
            if (!($child instanceof \DOMElement) || !($child->ownerDocument instanceof \DOMNode)) {
                continue;
            }
            if ($child->tagName !== 'template' && $child->tagName !== 'footer' && $child->tagName !== 'nav') {
                $body .= $child->ownerDocument->saveXML($child);
            }
        }

        return $body;
    }


}
