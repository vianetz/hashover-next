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

final class BlocklistHandler extends AbstractHandler
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $blocklist = [];

        $blocklist_file = $this->hashover->setup->getAbsolutePath('config/blocklist.json');

        $parsedBody = $request->getParsedBody();
        if (! empty($parsedBody['addresses']) && \is_array($parsedBody['addresses'])) {
            foreach ($parsedBody['addresses'] as $address) {
                if (! empty($address)) {
                    $blocklist[] = $address;
                }
            }

            if ($this->hashover->login->verifyAdmin()) {
                $saved = $this->dataFiles->saveJSON($blocklist_file, $blocklist);

                if ($saved) {
                    return $this->redirect($request, './?status=success');
                }
            }

            return $this->redirect($request, './?status=failure');
        }

        $json = $this->dataFiles->readJSON($blocklist_file);

        if (\is_array($json)) {
            $blocklist = $json;
        }

        $inputs = [];
        for ($i = 0, $il = max(3, count($blocklist)); $i < $il; $i++) {
            $inputs[] = [
                'value' => Misc::getArrayItem($blocklist, $i) ?: '',
                'title' => $this->hashover->locale->text['blocklist-ip-tip'],
            ];
        }

        $template = [
            'title' => $this->hashover->locale->text['blocklist-title'],
            'subTitle' => $this->hashover->locale->text['blocklist-sub'],
            'inputs' => $inputs,
            'saveButton' => $this->hashover->locale->text['save'],
        ];

        return $this->render('blocklist.html', $template);
    }
}
