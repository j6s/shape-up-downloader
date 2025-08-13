<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Command;

use J6s\ShapeUpDownloader\ContentModifier\PageContentModifier;
use J6s\ShapeUpDownloader\Service\ChapterContentExtractor;
use J6s\ShapeUpDownloader\Service\ChapterUrlProvider;
use J6s\ShapeUpDownloader\Service\QueryService;
use J6s\ShapeUpDownloader\Service\TableOfContentsExtractor;
use Safe\Exceptions\FilesystemException;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function Safe\file_get_contents;
use function Safe\file_put_contents;

/** @api */
class DownloadSingleHtmlCommand extends Command
{
    private const string BASE_URL = 'https://basecamp.com';
    private const string INDEX_URL = self::BASE_URL . '/shapeup/webbook';
    private const string OUTPUT_FILE = __DIR__ . '/../../output/shape-up.html';

    public function __construct(
        private QueryService $queryService,
        protected AbstractAdapter $cache,
        private PageContentModifier $contentModifier,
        private TableOfContentsExtractor $tocExtractor,
        private ChapterContentExtractor $chapterContentExtractor
    ) {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('download:single-html');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $overviewPage = $this->queryService->getDocument(self::INDEX_URL);
        $urls = $this->tocExtractor->extractChapterUrls($overviewPage, self::BASE_URL);
        $overviewPage = $this->contentModifier->modify($overviewPage, $urls);

        $progress = new ProgressBar($output);
        $progress->start(count($urls));

        $body = $this->getStyle();
        $body .= $this->tocExtractor->extractTableOfContentsHtml($overviewPage);

        foreach ($urls as $url) {
            $document = $this->queryService->getDocument($url);
            $document = $this->contentModifier->modify($document, $urls);

            $body .= $this->chapterContentExtractor->extractTitle($document);
            $body .= $this->chapterContentExtractor->extractBodyText($document);

            file_put_contents(self::OUTPUT_FILE, $body);
            $progress->advance();
        }

        $progress->finish();
        file_put_contents(self::OUTPUT_FILE, $body);

        return 0;
    }

    /**
     * @throws FilesystemException
     */
    private function getStyle(): string
    {
        $css = file_get_contents(__DIR__ . '/../style.css');
        return sprintf('<style>%s</style>', $css);
    }
}
