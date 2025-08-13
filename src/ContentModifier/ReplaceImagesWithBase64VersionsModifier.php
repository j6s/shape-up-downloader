<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\ContentModifier;

use Exception;
use J6s\ShapeUpDownloader\Exceptions\ModificationException;
use J6s\ShapeUpDownloader\Exceptions\RequestException;
use J6s\ShapeUpDownloader\Service\QueryService;
use Safe\Exceptions\PcreException;
use Symfony\Component\DomCrawler\Crawler;

use function Safe\preg_match;

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
        try {
            $images = $document->filter('img')->images();
        } catch (\LogicException $e) {
            throw new ModificationException(
                sprintf('Cannot extract images from the document: %s', $e->getMessage()),
                previous: $e
            );
        }

        foreach ($images as $image) {
            $imageUri = $image->getUri();
            $mimeType = $this->getMimeType($imageUri);

            try {
                $imageContent = $this->queryService->cachedRequest($imageUri);
            } catch (RequestException $e) {
                throw new ModificationException(
                    sprintf('Failed to fetch image from URL "%s": %s', $imageUri, $e->getMessage()),
                    previous: $e
                );
            }

            $base64 = sprintf(
                'data:%s;base64,%s',
                $mimeType,
                base64_encode($imageContent)
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

    /**
     * @throws ModificationException
     */
    private function getMimeType(string $imageUri): string
    {
        foreach (self::FILE_TYPES as $regex => $mime) {
            try {
                if (preg_match($regex, $imageUri)) {
                    return $mime;
                }
            } catch (PcreException $e) {
                throw new ModificationException(sprintf(
                    'Failed to match regex "%s" against image URI "%s" to check if it is of type "%s": %s',
                    $regex,
                    $imageUri,
                    $mime,
                    $e->getMessage()
                ), previous: $e);
            }
        }

        throw new ModificationException('Could not determine mime type for ' . $imageUri);
    }
}
