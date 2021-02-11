<?php
declare(strict_types=1);

namespace HashOver\Build;

use MatthiasMullie\Minify\JS;

final class MullieMinifier implements Minifier
{
    private JS $minifier;

    public function __construct(\MatthiasMullie\Minify\JS $js)
    {
        $this->minifier = $js;
    }

    public function add(string $filename): void
    {
        $this->minifier->addFile($filename);
    }

    public function minify(): string
    {
        return $this->minifier->minify();
    }
}