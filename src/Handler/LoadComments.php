<?php
declare(strict_types=1);

// Copyright (C) 2010-2019 Jacob Barkdull
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

namespace HashOver\Handler;

use HashOver\Misc;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LoadComments extends Javascript
{
    private \HashOver $hashover;
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response, \HashOver $hashover)
    {
        $this->response = $response;
        $this->hashover = $hashover;
    }
    
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->setNonCache($this->response);
        $response = $this->setContentType($request, $response);

        $this->hashover->setMode(\HashOver::HASHOVER_MODE_JSON);

        $this->hashover->setup->setPageURL($request);

        // Set page title from POST/GET data
        $this->hashover->setup->setPageTitle('request');

        // Set thread name from POST/GET data
        $this->hashover->setup->setThreadName('request');

        // Set website from POST/GET data
        $this->hashover->setup->setWebsite('request');

        // Initiate comment processing
        $this->hashover->initiate();

        // Check for comments
        if ($this->hashover->thread->totalCount > 1) {
            // Parse primary comments
            $this->hashover->parsePrimary();

            // Display as JSON data
            $data = $this->hashover->comments;

            if ($this->hashover->setup->enableStatistics) {
                $this->hashover->statistics->executionEnd();

                $data['statistics'] = [
                    'execution-time' => $this->hashover->statistics->executionTime,
                    'script-memory' => $this->hashover->statistics->scriptMemory,
                    'system-memory' => $this->hashover->statistics->systemMemory
                ];
            }
        } else {
            $data = array('No comments.');
        }

        $response->getBody()->write(Misc::jsonData($data));
        return $response;
    }
}
