<?php
declare(strict_types=1);

namespace HashOver\Build;

use Composer\Script\Event;

require_once __DIR__ . '/../autoload.php';

final class BuildJsScript
{
    private const OUTPUT_DIR = __DIR__ . '/../../public/static/dist/';

    public static function run(Event $event): void
    {
        $container = require __DIR__ . '/../../config/container.php';

        file_put_contents(self::OUTPUT_DIR . 'comments.js', $container->get(CommentsJs::class)->generate());
        file_put_contents(self::OUTPUT_DIR . 'loader.js', $container->get(LoaderJs::class)->generate());

        $event->getIO()->write('Javascript build successfully saved to ' . self::OUTPUT_DIR);
    }
}