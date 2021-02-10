<?php
declare(strict_types=1);

// Copyright (C) 2018-2019 Jacob Barkdull
// This file is part of HashOver.
//
// HashOver is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// HashOver is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with HashOver.  If not, see <http://www.gnu.org/licenses/>.

namespace HashOver;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../backend/standard-setup.php';

use FastRoute\RouteCollector;
use HashOver\Admin\Handler\LoginHandler;
use HashOver\Admin\Handler\ModerationHandler;
use HashOver\Admin\Handler\RedirectHandler;
use HashOver\Admin\Handler\ThreadsHandler;

setup_autoloader();

$dispatcher = \FastRoute\simpleDispatcher(static function (\FastRoute\RouteCollector $r): void {
    $r->addGroup('/admin', static function (RouteCollector $r): void {
        $r->addRoute('GET', '/', RedirectHandler::class);
        $r->addRoute(['GET', 'POST'], '/login/', LoginHandler::class);
        $r->addRoute('GET', '/moderation/', ModerationHandler::class);
        $r->addRoute('GET', '/moderation/threads/', ThreadsHandler::class);
    });
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rtrim(rawurldecode($uri), '/') . '/';

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        break;
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case \FastRoute\Dispatcher::FOUND:
        /** @var \HashOver\Admin\Handler\HandlerInterface $handler */
        $handler = new $routeInfo[1]();
        $vars = $routeInfo[2];

        $handler->run();

        break;
}