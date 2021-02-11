<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use HashOver\Build\JavaScriptBuild;

$definitions = [
    Psr\Log\LoggerInterface::class => static function (\Psr\Container\ContainerInterface $c) {
        $logger = new \Monolog\Logger('hashover-logger');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
        return $logger;
    },
    \HashOver\Build\JavaScriptBuild::class => static function (\Psr\Container\ContainerInterface $c) {
        return new JavaScriptBuild($c->get(\HashOver\Build\Minifier::class), '../../frontend');
    },
    \HashOver\Build\Minifier::class => \DI\autowire(\HashOver\Build\MullieMinifier::class),
    \HashOver\Build\CommentsJs::class => DI\decorate(static function ($previous, \Psr\Container\ContainerInterface $c) {
        return new \HashOver\Build\StatisticsDecorator($c->get(\HashOver\Statistics::class), $previous, $c->get(\HashOver\Setup::class));
    }),
    \HashOver\Build\LoaderJs::class => DI\decorate(static function ($previous, \Psr\Container\ContainerInterface $c) {
        return new \HashOver\Build\StatisticsDecorator($c->get(\HashOver\Statistics::class), $previous, $c->get(\HashOver\Setup::class));
    }),
    \Psr\Http\Message\ResponseInterface::class => \DI\create(\Laminas\Diactoros\Response::class),
];

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAnnotations(false);
$containerBuilder->enableCompilation(__DIR__ . '/tmp');
$containerBuilder->writeProxiesToFile(true, __DIR__ . '/tmp/proxies');
$containerBuilder->addDefinitions($definitions);

return $containerBuilder->build();