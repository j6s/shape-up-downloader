<?php declare(strict_types=1);

namespace J6s\ShapeUpDownloader\ContentModifier;

use Symfony\Component\DomCrawler\Crawler;

class PageContentModifierCollection implements PageContentModifier
{
    /** @param iterable<PageContentModifier> $modifiers */
    public function __construct(
        private iterable $modifiers
    ) { }

    public function modify(Crawler $document, array $urls): Crawler
    {
        foreach ($this->modifiers as $modifier) {
            $document = $modifier->modify($document, $urls);
        }
        return $document;
    }
}
