<?php declare(strict_types=1);
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

$application = new Application();
$input = new ArgvInput();
$output = new ConsoleOutput();

try {
    $container = new ContainerBuilder();
    $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/src'));
    $loader->load('services.xml');

    $container->compile();

    foreach (array_keys($container->findTaggedServiceIds('console.command')) as $service) {
        $application->add($container->get($service));
    }
    $application->run($input, $output);

} catch (Throwable $e) {
    $application->renderThrowable($e, $output);
}

