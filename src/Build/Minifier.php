<?php
declare(strict_types=1);

namespace HashOver\Build;

interface Minifier
{
    public function add(string $filename): void;

    public function minify(): string;
}