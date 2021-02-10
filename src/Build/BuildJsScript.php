<?php
declare(strict_types=1);

namespace HashOver\Build;

use Composer\Script\Event;
use HashOver\Handler\CommentsJsHandler;
use HashOver\Setup;
use HashOver\Statistics;

require __DIR__ . '/../../backend/standard-setup.php';

final class BuildJsScript
{
    public static function run(Event $event): void
    {
        $handler = new CommentsJsHandler(new Setup(), new Statistics());
        $handler->run();
    }
}