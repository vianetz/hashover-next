<?php
declare(strict_types=1);

namespace HashOver\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Javascript extends NonCacheable
{
    protected function setContentType(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        return isset($queryParams['jsonp']) ?
            $response->withHeader('Content-Type', 'application/json') :
            $response->withHeader('Content-Type', 'application/javascript');
    }
}