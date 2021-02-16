<?php
declare(strict_types=1);

namespace HashOver\Build;

use Composer\Script\Event;
use DI\Container;
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

        self::linkThemeCss(__DIR__ . '/../../templates/themes');

        self::generateAdminJs($setup, $container);

        $event->getIO()->write('Javascript build successfully saved to ' . self::OUTPUT_DIR);
        $event->getIO()->write('You can integrate comments now with');
        $sri = self::generateSri(file_get_contents(__DIR__ . '/../../public/static/dist/loader.js'));
        $event->getIO()->write('    <script type="text/javascript" src="/static/dist/loader.js" integrity="' . $sri .'" crossorigin="anonymous" async defer></script>');
    }

    private static function generateAdminJs(Setup $setup, Container $container): void
    {
        $setupClone = clone $setup;
        $setupClone->theme = 'default';
        $setupClone->appendsCss = true;
        $setupClone->defaultSorting = 'by-date';
        $setupClone->collapsesInterface = false;
        $setupClone->collapsesComments = false;
        $setupClone->formPosition = 'bottom';
        $setupClone->passwordField = 'off';

        self::writeToFile('admin/dist/comments.js', $container->get(CommentsJs::class)->generate($setupClone));
        self::writeToFile('admin/dist/loader.js', str_replace("'/static/dist/comments.js'", "'/static/admin/dist/comments.js'", $container->get(LoaderJs::class)->generate($setupClone)));
    }

    private static function writeToFile(string $filename, string $contents): void
    {
        self::createFolders(self::OUTPUT_DIR . \dirname($filename));
        file_put_contents(self::OUTPUT_DIR . $filename, $contents);
    }

    private static function createFolders(string $pathname): void
    {
        if (is_dir($pathname)) {
            return;
        }

        if (! mkdir($pathname, 0777, true) && ! is_dir($pathname)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $pathname));
        }
    }

    private static function generateSri(string $data): string
    {
        return 'sha384-' . base64_encode(hash('sha384', $data, true));
    }

    private static function linkThemeCss(string $src): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src));
        $destination = [];
        $origin = [];
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir() || $file->getExtension() !== 'css') {
                continue;
            }

            $origin = $file->getPathname();
            $destinationDir = __DIR__ . '/../../public/static/dist/themes' . str_replace($src, '', $file->getPath());
            $destination = $destinationDir . '/' . $file->getFilename();

            self::createFolders($destinationDir);
            @copy($origin, $destination);
        }
    }
}