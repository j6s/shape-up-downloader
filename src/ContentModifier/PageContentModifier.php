<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\ContentModifier;

use Symfony\Component\DomCrawler\Crawler;

interface PageContentModifier
{
    public function modify(Crawler $document, array $urls): Crawler;
}
