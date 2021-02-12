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

namespace HashOver\Admin\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LoginHandler extends AbstractHandler
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        if (! empty($parsedBody['name']) && ! empty($parsedBody['password'])) {
            $this->hashover->login->setAdminLogin();

            if ($this->hashover->login->isAdmin()) {
                $this->hashover->login->adminLogin();
            } else {
                $this->hashover->login->clearLogin();
                sleep(5);
            }

            return $this->redirect($request);
        }

        if (isset($queryParams['logout'])) {
            $this->hashover->login->clearLogin();

            $admin_path = $this->hashover->setup->getHttpPath('admin');

            return $this->redirect($request, $admin_path . '/');
        }

        return $this->render('login.html');
    }
}