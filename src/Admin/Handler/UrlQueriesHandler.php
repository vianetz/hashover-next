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

use HashOver\Misc;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UrlQueriesHandler extends AbstractHandler
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $ignoredQueries = [];
        $parsedBody = $request->getParsedBody();

        $queriesFile = $this->hashover->setup->getAbsolutePath('config/ignored-queries.json');

        if (! empty($parsedBody['names']) && \is_array($parsedBody['names'])
            && ! empty($parsedBody['values']) && \is_array($parsedBody['values'])) {
            for ($i = 0, $il = \count($parsedBody['names']); $i < $il; $i++) {
                if (empty($parsedBody['names'][$i])) {
                    continue;
                }

                $queryPair = $parsedBody['names'][$i];
                if (! empty($parsedBody['values'][$i])) {
                    $queryPair .= '=' . $parsedBody['values'][$i];
                }

                $ignoredQueries[] = $queryPair;
            }

            if ($this->hashover->login->verifyAdmin()) {
                $saved = $this->dataFiles->saveJSON($queriesFile, $ignoredQueries);

                if ($saved) {
                    return $this->redirect($request, './?status=success');
                }
            }

            return $this->redirect($request, './?status=failure');
        }

        $json = $this->dataFiles->readJSON($queriesFile);
        if (\is_array($json)) {
            $ignoredQueries = $json;
        }

        $inputs = [];
        for ($i = 0, $il = max(3, \count($ignoredQueries)); $i < $il; $i++) {
            $query = Misc::getArrayItem($ignoredQueries, $i) ?: '';

            $queryParts = explode('=', $query);

            $inputs[] = [
                'nameValue' => $queryParts[0],
                'namePlaceholder' => $this->hashover->locale->text['name'],
                'nameTip' => $this->hashover->locale->text['url-queries-name-tip'],
                'value' => Misc::getArrayItem($queryParts, 1) ?: '',
                'valuePlaceholder' => $this->hashover->locale->text['value'],
                'valueTip' => $this->hashover->locale->text['url-queries-value-tip'],
            ];
        }

        $template = [
            'title' => $this->hashover->locale->text['url-queries-title'],
            'subTitle' => $this->hashover->locale->text['url-queries-sub'],
            'inputs' => $inputs,
            'saveButton' => $this->hashover->locale->text['save'],
        ];

        return $this->render('url-queries.html', $template);
    }
}
