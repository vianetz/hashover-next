<?php
declare(strict_types=1);

namespace HashOver\Domain;

use HashOver\Helper\RequestHelper;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final class Url
{
    private RequestHelper $requestHelper;

    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }
    public function getPageUrl(ServerRequestInterface $request): UriInterface
    {
        $url = $this->retrievePageUrl($request);

        $uri = new Uri($url);

        if (empty($uri->getHost()) || empty($uri->getScheme())) {
            throw new \Exception('URL needs a hostname and scheme.');
        }

        return $uri;
    }

    private function retrievePageUrl(ServerRequestInterface $request): string
    {
        $url = $this->requestHelper->getPostOrGet($request, 'url');
        if (! empty($url)) {
            return $url;
        }

        $serverParams = $request->getServerParams();
        if (! empty($serverParams['HTTP_REFERER'])) {
            return $serverParams['HTTP_REFERER'];
        }

        throw new \Exception('Failed to obtain page URL.');
    }
}