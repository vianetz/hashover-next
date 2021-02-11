<?php
declare(strict_types=1);

namespace HashOver\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Javascript
{
    protected function setNonCache(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader('Expires', 'Wed, 08 May 1991 12:00:00 GMT')
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withAddedHeader('Cache-Control', 'post-check=0, pre-check=0')
            ->withHeader('Pragma', 'no-cache');
    }

    protected function setContentType(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        return isset($queryParams['jsonp']) ?
            $response->withHeader('Content-Type', 'application/json') :
            $response->withHeader('Content-Type', 'application/javascript');
    }
}