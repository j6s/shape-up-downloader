<?php declare(strict_types=1);
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
$application = new Application();
$application->add(new \J6s\ShapeUpDownloader\DownloadSingleHtmlCommand());
$application->run();