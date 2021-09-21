<?php declare(strict_types=1);

namespace J6s\ShapeUpDownloader\ContentModifier;

use Symfony\Component\DomCrawler\Crawler;

class PageContentModifierCollection implements PageContentModifier
{
    /** @var PageContentModifier[] */
    private array $modifiers;

    // TODO Use service tag here
    public function __construct(
        ReplaceImagesWithBase64VersionsModifier $replaceImagesWithBase64VersionsModifier,
        ReplaceLinksWithInternalLinksModifier $replaceLinksWithInternalLinksModifier
    ){
        $this->modifiers = [
            $replaceImagesWithBase64VersionsModifier,
            $replaceLinksWithInternalLinksModifier
        ];
    }

    public function modify(Crawler $document, array $urls): Crawler
    {
        foreach ($this->modifiers as $modifier) {
            $document = $modifier->modify($document, $urls);
        }
        return $document;
    }
}
