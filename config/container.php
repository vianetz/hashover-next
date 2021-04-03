<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use HashOver\Build\JavaScriptBuild;

$definitions = [
    Psr\Log\LoggerInterface::class => static function (\Psr\Container\ContainerInterface $c) {
        $logger = new \Monolog\Logger('hashover-logger');
        $handler = new \Monolog\Handler\StreamHandler($_ENV['LOG_FILE'], $_ENV['LOG_LEVEL']);
        $handler->setFormatter(new \Monolog\Formatter\LogstashFormatter('hashover-logger'));
        $logger->pushHandler($handler);

        return $logger;
    },
    \HashOver\Build\JavaScriptBuild::class => static function (\Psr\Container\ContainerInterface $c) {
        return new JavaScriptBuild($c->get(\HashOver\Build\MullieMinifierFactory::class), '../js');
    },
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
        $latte->addFilter('translate', [$c->get(\HashOver\Domain\Translator::class), 'translate']);
        $latte->addFilter('hoPrefix', [$c->get(\HashOver\Helper\TemplateHelper::class), 'prefix']);
        $latte->addFilter('hoSuffix', [$c->get(\HashOver\Helper\TemplateHelper::class), 'suffix']);
        $latte->addFilter('addQueryParams', [$c->get(\HashOver\Helper\TemplateHelper::class), 'createQueryString']);
        $latte->addFilter('avatar', [$c->get(\HashOver\Backend\Avatar::class), 'getAvatarHtml']);

        return $latte;
    },
];

$config = require __DIR__ . '/global.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAnnotations(false);
if ($config['enableContainerCompile']) {
    $containerBuilder->enableCompilation($config['tmpDir']);
    $containerBuilder->writeProxiesToFile(true, $config['tmpDir'] . '/proxies');
}
$containerBuilder->addDefinitions($definitions);

return $containerBuilder->build();