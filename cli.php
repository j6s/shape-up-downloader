<?php declare(strict_types=1);
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

$container = new ContainerBuilder();
$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/src'));
$loader->load('services.xml');

$container->compile();

$application = new Application();
foreach (array_keys($container->findTaggedServiceIds('console.command')) as $service) {
    $application->add($container->get($service));
}
$application->run();
