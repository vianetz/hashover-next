<?php
declare(strict_types=1);

return [
    Psr\Log\LoggerInterface::class => static function (\Psr\Container\ContainerInterface $c) {
        $logger = new \Monolog\Logger('hashover-logger');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
        return $logger;
    },
    'CommentsJsHandler' => static function (\Psr\Container\ContainerInterface $c) {
        return new \HashOver\Handler\CommentsJsHandler(new \HashOver\Setup(), new \HashOver\Statistics());
    },
    'Setup' => \DI\create(HashOver\Setup::class),
];