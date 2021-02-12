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

use HashOver\DataFiles;
use HashOver\Locale;
use Latte\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractHandler
{
    protected \HashOver $hashover;
    protected DataFiles $dataFiles;
    protected ResponseInterface $response;
    protected Engine $latte;

    public function __construct(\HashOver $hashover, Locale $locale, DataFiles $dataFiles, ResponseInterface $response, Engine $latte)
    {
        $this->hashover = $hashover;
        $this->hashover->setup->setsCookies = true;
        $this->hashover->locale = $locale;
        $this->dataFiles = $dataFiles;
        $this->response = $response;
        $this->latte = $latte;

        $this->checkAllowed();
    }

    protected function redirect(ServerRequestInterface $request, string $url = ''): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        if (! empty($url)) {
            $response = $this->response->withHeader('Location', $url);
        } elseif (! empty($queryParams['redirect'])) {
            $response = $this->response->withHeader('Location', $queryParams['redirect']);
        } else {
            $response = $this->response->withHeader('Location', '../moderation/');
        }

        return $response;
    }

    protected function render(string $templateName, array $templateData): ResponseInterface
    {
        $templateData = $this->mergeTemplateData($templateData);

        $response = $this->response;
        $response->getBody()->write($this->latte->renderToString(APP_DIR . '/templates/admin/' . $templateName, $templateData));

        return $response;
    }

    private function mergeTemplateData(array $data): array
    {
        $language = str_replace('_', '-', strtolower($this->hashover->setup->language));
        $language = file_exists('/docs/' . $language) ? $language : 'en-us';

        $data = array_merge($data, [
            'root' => rtrim($this->hashover->setup->httpRoot, '/'),
            'admin' => $this->hashover->setup->getHttpPath('admin'),
            'moderation' => $this->hashover->locale->text['moderation'],
            'ipBlocking' => $this->hashover->locale->text['block-ip-addresses'],
            'urlFiltering' => $this->hashover->locale->text['filter-url-queries'],
            'settings' => $this->hashover->locale->text['settings'],
            'updates' => $this->hashover->locale->text['check-for-updates'],
            'docs' => $this->hashover->locale->text['documentation'],
            'logout' => $this->hashover->locale->text['logout'],
            'language' => $language,
        ]);

        if (! empty($_GET['status'])) {
            if ($_GET['status'] === 'success') {
                $data['message'] = $this->hashover->locale->text['successful-save'];
                $data['messageStatus'] = 'success';
            } else {
                $data['message'] = $this->hashover->locale->text['failed-to-save'];
                $data['error'] = $this->hashover->locale->permissionsInfo('config');
                $data['messageStatus'] = 'error';
            }
        }

        return $data;
    }

    protected function checkAllowed(): void
    {
        if ($this->hashover->login->userIsAdmin) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'];
        $uri_parts = explode('?', $uri);

        if (basename($uri_parts[0]) !== 'login') {
            $this->redirect('../login/?redirect=' . urlencode($uri));
        }
    }
}
