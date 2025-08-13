<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\ContentModifier;

use Exception;
use J6s\ShapeUpDownloader\Service\QueryService;
use Symfony\Component\DomCrawler\Crawler;

class ReplaceImagesWithBase64VersionsModifier implements PageContentModifier
{
    private const FILE_TYPES = [
        '/\\.jpe?g$/' => 'image/jpeg',
        '/\\.png$/' => 'image/png',
        '/\\.gif/' => 'image/gif',
    ];

    public function __construct(
        private QueryService $queryService
    ) {
    }

    public function modify(Crawler $document, array $urls): Crawler
    {
        foreach ($document->filter('img')->images() as $image) {
            $imageUri = $image->getUri();
            $mimeType = null;
            foreach (self::FILE_TYPES as $regex => $mime) {
                if (preg_match($regex, $imageUri)) {
                    $mimeType = $mime;
                    break;
                }
            }
            if ($mimeType === null) {
                throw new Exception('Could not determine mime type for ' . $imageUri);
            }

            $base64 = sprintf(
                'data:%s;base64,%s',
                $mimeType,
                base64_encode($this->queryService->cachedRequest($imageUri))
            );

            $node = $image->getNode();
            $parent = $node->parentNode;
            $node->setAttribute('src', $base64);

            // Remove wrapping <a tag
            if ($parent && $parent->parentNode && $parent->nodeName === 'a') {
                $parent->parentNode->insertBefore($node, $parent);
                $parent->parentNode->removeChild($parent);
            }
        }

        return $document;
    }
}
