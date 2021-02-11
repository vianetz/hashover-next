<?php
declare(strict_types=1);

namespace HashOver\Admin\Handler;

use HashOver;
use Psr\Http\Message\ResponseInterface;

final class RedirectHandler
{
    private HashOver $hashover;
    private ResponseInterface $response;

    public function __construct(HashOver $hashover, ResponseInterface $response)
    {
        $this->hashover = $hashover;
        $this->response = $response;
    }

    public function __invoke(): ResponseInterface
    {
        if ($this->hashover->login->isAdmin()) {
            $response = $this->response->withHeader('Location', '/admin/moderation/');
        } else {
            $response = $this->response->withHeader('Location', '/admin/login/');
        }

        return $response;
    }
}