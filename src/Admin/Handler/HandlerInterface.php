<?php
declare(strict_types=1);

namespace HashOver\Admin\Handler;

interface HandlerInterface
{
    public function run(): void;
}