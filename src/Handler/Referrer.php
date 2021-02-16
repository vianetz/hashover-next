<?php
declare(strict_types=1);

namespace HashOver\Handler;

use HashOver\Setup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Referrer implements MiddlewareInterface
{
    private Setup $setup;

    public function __construct(Setup $setup)
    {
        $this->setup = $setup;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        if (empty($serverParams['HTTP_REFERER'])) {
            $this->setup->setupRemoteAccess();
            return $handler->handle($request);
        }

        $domain = $this->getDomainWithPort($serverParams['HTTP_REFERER']);
        if ($domain === $this->setup->domain) {
            return $handler->handle($request);
        }

        $sub_regex = '/^' . preg_quote('\*\.') . '/S';

        foreach ($this->setup->allowedDomains as $allowed_domain) {
            $safe_domain = preg_quote($allowed_domain);

            $domain_regex = preg_replace($sub_regex, '(?:.*?\.)*', $safe_domain);

            $domain_regex = '/^' . $domain_regex . '$/iS';

            // Check if script was requested from an allowed domain
            if (preg_match($domain_regex, $domain)) {
                $this->setup->setupRemoteAccess();
                return $handler->handle($request);
            }
        }

        throw new \Exception('External use not allowed.');
    }

    private function getDomainWithPort(string $url = '')
    {
        $url = parse_url($url);

        // Throw exception if URL or host is empty
        if ($url === false or empty ($url['host'])) {
            throw new \Exception (
                'Failed to obtain domain name.'
            );
        }

        // Otherwise, return domain without port
        return $url['host'] . (!empty($url['port']) ? ':' . $url['port'] : '');
    }
}