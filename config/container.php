<?php
declare(strict_types=1);

return [
    \Monolog\Logger::class => new \Monolog\Logger('hashover-logger'),
    Monolog\Handler\StreamHandler::class => new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG),
];