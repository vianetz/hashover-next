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
    \Psr\Http\Message\ServerRequestInterface::class => \DI\create(\Laminas\Diactoros\ServerRequest::class),
    Swift_Mailer::class => static function (\Psr\Container\ContainerInterface $c) {
        if ($c->get(\HashOver\Setup::class)->mailer !== 'sendmail') {
            $transport = new Swift_SmtpTransport((string)$_ENV['SMTP_HOST'], (int)$_ENV['SMTP_PORT']);
            $transport->setUsername((string)$_ENV['SMTP_USER'])
                ->setPassword((string)$_ENV['SMTP_PASSWORD']);
        } else {
            $transport = new Swift_SendmailTransport(ini_get('sendmail_path'));
        }

        return new Swift_Mailer($transport);
    },
    Latte\Engine::class => static function (\Psr\Container\ContainerInterface $c) {
        $latte = new \Latte\Engine();
        $latte->setTempDirectory(sys_get_temp_dir());
        return $latte;
    },
];

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAnnotations(false);
$containerBuilder->enableCompilation(__DIR__ . '/tmp');
$containerBuilder->writeProxiesToFile(true, __DIR__ . '/tmp/proxies');
$containerBuilder->addDefinitions($definitions);

return $containerBuilder->build();