<?php
declare(strict_types=1);

namespace HashOver\Helper;

use Psr\Http\Message\ServerRequestInterface;

final class RequestHelper
{
    public function getPostOrGet(ServerRequestInterface $request, string $parameterName)
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        if (isset($parsedBody[$parameterName])) {
            return $parsedBody[$parameterName];
        }

        return $queryParams[$parameterName] ?? null;
    }

    public function hasPostOrGet(ServerRequestInterface $request, string $parameterName): bool
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        return isset($parsedBody[$parameterName]) || isset($queryParams[$parameterName]);
    }
}