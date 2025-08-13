<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\ContentModifier;

use J6s\ShapeUpDownloader\Exceptions\ModificationException;
use Symfony\Component\DomCrawler\Crawler;

interface PageContentModifier
{
    /**
     * @throws ModificationException
     * @param list<string> $urls
     */
    public function modify(Crawler $document, array $urls): Crawler;
}
