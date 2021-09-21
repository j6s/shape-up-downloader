<?php

namespace J6s\ShapeUpDownloader;

use Generator;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use function Safe\preg_replace;

class DownloadSingleHtmlCommand extends Command
{
    protected static $defaultName = 'download:single-html';

    protected AbstractAdapter $cache;
    protected string $indexUrl = 'https://basecamp.com/shapeup/webbook';

    /** @var string[] */
    protected array $urls = [
        'https://basecamp.com/shapeup/0.1-foreword',
        'https://basecamp.com/shapeup/0.2-acknowledgements',
        'https://basecamp.com/shapeup/0.3-chapter-01',
        'https://basecamp.com/shapeup/1.1-chapter-02',
        'https://basecamp.com/shapeup/1.2-chapter-03',
        'https://basecamp.com/shapeup/1.3-chapter-04',
        'https://basecamp.com/shapeup/1.4-chapter-05',
        'https://basecamp.com/shapeup/1.5-chapter-06',
        'https://basecamp.com/shapeup/2.1-chapter-07',
        'https://basecamp.com/shapeup/2.2-chapter-08',
        'https://basecamp.com/shapeup/2.3-chapter-09',
        'https://basecamp.com/shapeup/3.1-chapter-10',
        'https://basecamp.com/shapeup/3.2-chapter-11',
        'https://basecamp.com/shapeup/3.3-chapter-12',
        'https://basecamp.com/shapeup/3.4-chapter-13',
        'https://basecamp.com/shapeup/3.5-chapter-14',
        'https://basecamp.com/shapeup/3.6-chapter-15',
        'https://basecamp.com/shapeup/3.7-conclusion',
        'https://basecamp.com/shapeup/4.0-appendix-01',
        'https://basecamp.com/shapeup/4.1-appendix-02',
        'https://basecamp.com/shapeup/4.2-appendix-03',
        'https://basecamp.com/shapeup/4.5-appendix-06',
        'https://basecamp.com/shapeup/4.6-appendix-07',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->cache = new FilesystemAdapter();
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $progress = new ProgressBar($output);
        $progress->start(\count($this->urls));

        $body = '';
        $body .= $this->minimalCss();
        $body .= $this->toc();

        foreach ($this->fullDocuments() as $document) {
            $title = $this->extractTitle($document);
            $content = $this->extractContent($document);

            $body .= (string) $title;
            $body .= $content;
            file_put_contents('shape-up.html', $body);
            $progress->advance();
        }

        $progress->finish();
        file_put_contents('shape-up.html', $body);
    }

    private function extractTitle(Crawler $document): ?string
    {
        $uri = $document->getUri();
        if ($uri === null) {
            return null;
        }

        $chapterNumberElement = $document->filter('.intro__masthead');
        $chapterNumber = $chapterNumberElement->count() > 0 ? $chapterNumberElement->text() : '';

        $titleElement = $document->filter('.intro__title > a');
        $title = $titleElement->text();

        return sprintf(
            '<h1 id="%s">%s %s</h1>',
            $this->urlToInternal($uri),
            $chapterNumber,
            $title
        );
    }

    private function extractContent(Crawler $document): string
    {
        $body = '';

        $this->imageUrlsToBase64($document);
        $this->replaceLinksWithInternalOnes($document);

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

    private function imageUrlsToBase64(Crawler $document): void
    {
        $fileTypes = [
            '/\\.jpg$/' => 'image/jpeg',
            '/\\.png$/' => 'image/png',
            '/\\.gif/' => 'image/gif',
        ];
        foreach ($document->filter('img')->images() as $image) {
            $imageUri = $image->getUri();
            $mimeType = null;
            foreach ($fileTypes as $regex => $mime) {
                if (preg_match($regex, $imageUri)) {
                    $mimeType = $mime;
                    break;
                }
            }
            if ($mimeType === null) {
                throw new \Exception('Could not determine mime type for ' . $imageUri);
            }

            $base64 = sprintf(
                'data:%s;base64,%s',
                $mimeType,
                base64_encode($this->cachedRequest($imageUri))
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
    }

    private function replaceLinksWithInternalOnes(Crawler $document): void
    {
        foreach ($document->filter('a')->links() as $link) {
            $uri = trim($link->getUri(), '/');

            $index = strpos($uri, '#');
            if (in_array($uri, $this->urls)) {
                // If it is a link to another page in this document
                $uri = '#' . $this->urlToInternal($uri);
            } elseif ($index !== false) {
                // If it already is a hash-based link
                $uri = substr($uri, $index);
            }


            $link->getNode()->setAttribute('href', $uri);
        }
    }

    private function urlToInternal(string $url): string
    {
        $url = str_replace('https://basecamp.com/', '', $url);
        return $this->regexReplace('/\W+/', '-', $url);
    }

    private function toc(): string
    {
        $document = new Crawler($this->cachedRequest($this->indexUrl), $this->indexUrl);
        $this->replaceLinksWithInternalOnes($document);
        return $document->filter('.toc')->html();
    }

    /**
     * @return Generator<Crawler>
     */
    private function fullDocuments(): Generator
    {
        foreach ($this->urls as $url) {
            yield new Crawler(
                $this->cachedRequest($url),
                $url
            );
        }
    }

    private function minimalCss(): string
    {
        return '
            <style>
                body {
                    font-family: serif;
                    font-size: 18px;
                    line-height: 1.5;
                }
                img {
                    max-width: 100%;
                }
            </style>
        ';
    }

    private function cachedRequest(string $url): string
    {
        $key = $this->regexReplace('/\W/', '-', $url);
        return (string) $this->cache->get($key, function() use ($url) {
            return file_get_contents($url);
        });
    }

    private function regexReplace(string $pattern, string $replacement, string $subject): string
    {
        $result = preg_replace($pattern, $replacement, $subject);

        if (is_string($result)) {
            return $result;
        }

        return implode($replacement);
    }
}
