<?php
declare(strict_types=1);

namespace HashOver\Build;

use MatthiasMullie\Minify\JS;

final class MullieMinifierFactory
{
    public function create(): MullieMinifier
    {
        return new MullieMinifier(new JS());
    }
}