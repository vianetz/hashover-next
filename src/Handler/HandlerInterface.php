<?php
declare(strict_types=1);

namespace HashOver\Handler;

interface HandlerInterface
{
    public function run(): void;
}