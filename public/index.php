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

require_once __DIR__ . '/../src/autoload.php';

use FastRoute\RouteCollector;
use HashOver\Admin\Handler\BlocklistHandler;
use HashOver\Admin\Handler\LoginHandler;
use HashOver\Admin\Handler\ModerationHandler;
use HashOver\Admin\Handler\RedirectHandler;
use HashOver\Admin\Handler\SettingsHandler;
use HashOver\Admin\Handler\ThreadsHandler;
use HashOver\Admin\Handler\UpdateHandler;
use HashOver\Admin\Handler\UrlQueriesHandler;
use HashOver\Handler\CommentInfo;
use HashOver\Handler\Comments;
use HashOver\Handler\FormActions;
use HashOver\Handler\LoadComments;
use HashOver\Handler\Referrer;
use Laminas\Diactoros\ServerRequestFactory;
use Middlewares\FastRoute;
use Middlewares\RequestHandler;
use Narrowspark\HttpEmitter\SapiEmitter;
use Relay\Relay;

$container = require __DIR__ . '/../config/container.php';

$dispatcher = \FastRoute\simpleDispatcher(static function (\FastRoute\RouteCollector $r): void {
    $r->addGroup('/admin', static function (RouteCollector $r): void {
        $r->addRoute('GET', '/', RedirectHandler::class);
        $r->addRoute(['GET', 'POST'], '/login/', LoginHandler::class);
        $r->addRoute('GET', '/moderation/', ModerationHandler::class);
        $r->addRoute('GET', '/moderation/threads/', ThreadsHandler::class);
        $r->addRoute(['GET', 'POST'], '/blocklist/', BlocklistHandler::class);
        $r->addRoute(['GET', 'POST'], '/url-queries/', UrlQueriesHandler::class);
        $r->addRoute(['GET', 'POST'], '/settings/', SettingsHandler::class);
        $r->addRoute(['GET', 'POST'], '/updates/', UpdateHandler::class);
    });
    $r->addRoute(['GET', 'POST'], '/comments', Comments::class);
    $r->addGroup('/backend', static function (RouteCollector $r): void {
        $r->addRoute(['GET', 'POST'], '/form-actions', FormActions::class);
        $r->addRoute(['GET', 'POST'], '/load-comments', LoadComments::class);
        $r->addRoute(['GET', 'POST'], '/comment-info', CommentInfo::class);
    });
});

$middlewareQueue = [];
$middlewareQueue[] = new FastRoute($dispatcher);
$middlewareQueue[] = new Referrer($container->get(Setup::class));
$middlewareQueue[] = new RequestHandler($container);

$requestHandler = new Relay($middlewareQueue);
$response = $requestHandler->handle(ServerRequestFactory::fromGlobals());

$emitter = new SapiEmitter();
$emitter->emit($response);