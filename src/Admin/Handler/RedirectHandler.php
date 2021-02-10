<?php
declare(strict_types=1);

namespace HashOver\Admin\Handler;

final class RedirectHandler implements HandlerInterface
{
    public function run(): void
    {
        $hashover = new \HashOver();

        if ($hashover->login->isAdmin()) {
            header('Location: moderation/');
        } else {
            header('Location: login/');
        }
    }
}