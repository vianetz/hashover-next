<?php
declare(strict_types=1);

namespace HashOver\Handler;

use HashOver\Domain\UserInputException;
use HashOver\Misc;
use HashOver\Setup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UserException implements MiddlewareInterface
{
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (UserInputException $exception) {
            $this->response->getBody()->write(Misc::displayException($exception, 'json'));
            return $this->response;
        }
    }
}