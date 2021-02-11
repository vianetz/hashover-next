<?php
declare(strict_types=1);

namespace HashOver\Helper;

use Psr\Http\Message\ServerRequestInterface;

final class RequestHelper
{
    public function getPostOrGet(ServerRequestInterface $request, string $parameterName)
    {
        $value = null;
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        if (isset($parsedBody[$parameterName])) {
            $value = $queryParams[$parameterName];
        }

        if (isset($queryParams[$parameterName])) {
            $value = $queryParams[$parameterName];
        }

        return $value;
    }

    public function hasPostOrGet(ServerRequestInterface $request, string $parameterName): bool
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        return isset($parsedBody[$parameterName]) || isset($queryParams[$parameterName]);
    }
}