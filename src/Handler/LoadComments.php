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
use HashOver\Setup;
use HashOver\Statistics;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LoadComments extends Javascript
{
    private \HashOver $hashover;
    private ResponseInterface $response;
    private Setup $setup;
    private Statistics $statistics;

    public function __construct(ResponseInterface $response, \HashOver $hashover, Setup $setup, Statistics $statistics)
    {
        $this->response = $response;
        $this->hashover = $hashover;
        $this->setup = $setup;
        $this->statistics = $statistics;
    }
    
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->setNonCache($this->response);
        $response = $this->setContentType($request, $response);

        $this->hashover->setMode(\HashOver::HASHOVER_MODE_JSON);

        $this->setup->setPageURL($request);
        $this->setup->setPageTitle('request');
        $this->setup->setThreadName('request');
        $this->setup->setWebsite('request');

        $this->setup->loadFrontendSettings($request);

        $this->hashover->initiate();

        if ($this->hashover->thread->totalCount > 1) {
            // Parse primary comments
            $this->hashover->parsePrimary();

            // Display as JSON data
            $data = $this->hashover->comments;

            if ($this->setup->enableStatistics) {
                $this->statistics->executionEnd();

                $data['statistics'] = [
                    'execution-time' => $this->statistics->executionTime,
                    'script-memory' => $this->statistics->scriptMemory,
                    'system-memory' => $this->statistics->systemMemory
                ];
            }
        } else {
            $data = ['No comments.'];
        }

        $response->getBody()->write(Misc::jsonData($data));
        return $response;
    }
}
