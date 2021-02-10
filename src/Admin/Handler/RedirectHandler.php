<?php
declare(strict_types=1);

namespace HashOver\Admin\Handler;

use HashOver\Handler\HandlerInterface;

final class RedirectHandler implements HandlerInterface
{
    public function run(): void
    {
        $hashover = new \HashOver();

        if ($hashover->login->isAdmin()) {
            header('Location: /admin/moderation/');
        } else {
            header('Location: /admin/login/');
        }
    }
}