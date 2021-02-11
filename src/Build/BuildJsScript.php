<?php
declare(strict_types=1);

namespace HashOver\Build;

use Composer\Script\Event;
use HashOver\Setup;

require_once __DIR__ . '/../autoload.php';

final class BuildJsScript
{
    private const OUTPUT_DIR = __DIR__ . '/../../public/static/';

    public static function run(Event $event): void
    {
        $container = require __DIR__ . '/../../config/container.php';

        /** @var Setup $setup */
        $setup = $container->get(Setup::class);

        self::writeToFile('dist/comments.js', $container->get(CommentsJs::class)->generate($setup));
        self::writeToFile('dist/loader.js', $container->get(LoaderJs::class)->generate($setup));

        $setup->theme = 'default';
        $setup->appendsCss = true;
        $setup->defaultSorting = 'by-date';
        $setup->collapsesInterface = false;
        $setup->collapsesComments = false;
        $setup->formPosition = 'bottom';
        $setup->passwordField = 'off';
        self::writeToFile('admin/dist/comments.js', $container->get(CommentsJs::class)->generate($setup));
        self::writeToFile('admin/dist/loader.js', str_replace("'/static/dist/comments.js'", "'/static/admin/dist/comments.js'", $container->get(LoaderJs::class)->generate($setup)));

        $event->getIO()->write('Javascript build successfully saved to ' . self::OUTPUT_DIR);
    }

    private static function writeToFile(string $filename, string $contents): void
    {
        file_put_contents(self::OUTPUT_DIR . $filename, $contents);
    }
}