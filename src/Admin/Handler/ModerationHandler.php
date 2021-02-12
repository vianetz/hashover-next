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
use HashOver\Helper\RequestHelper;
use HashOver\Locale;
use Latte\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ModerationHandler extends AbstractHandler
{
    private RequestHelper $requestHelper;

    public function __construct(\HashOver $hashover, Locale $locale, DataFiles $dataFiles, ResponseInterface $response, Engine $latte, RequestHelper $requestHelper)
    {
        parent::__construct($hashover, $locale, $dataFiles, $response, $latte);
        $this->requestHelper = $requestHelper;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $currentWebsite = $this->hashover->setup->website;
        $website = $this->requestHelper->getPostOrGet($request, 'website') ?? $currentWebsite;

        if ($website !== $currentWebsite) {
            $this->hashover->setup->setWebsite($website);
        }

        $threads = $this->hashover->thread->queryThreads();

        $allThreadData = [];
        foreach ($threads as $thread) {
            $data = $this->hashover->thread->data->readMeta('page-info', $thread);

            if (! $data || empty($data['url']) || empty($data['title'])) {
                continue;
            }

            $threadData = [];
            $threadData['link'] = 'threads/?' . http_build_query([
                'website' => $website,
                'thread' => $thread,
                'title' => $data['title'],
                'url' => $data['url'],
            ]);
            $threadData['title'] = $data['title'];
            $threadData['url'] = $data['url'];

            $allThreadData[] = $threadData;
        }

        $template = [
            'leftId' => 'threads-column',
            'threads' => $allThreadData,
            'currentWebsite' => $website,
        ];

        if ($this->hashover->setup->supportsMultisites) {
            $websites = $this->hashover->thread->queryWebsites();

            // Add domain to array of websites if it isn't present
            if (! \in_array($this->hashover->setup->domain, $websites)) {
                $websites[] = $this->hashover->setup->domain;
            }

            $allWebsitesData = [];
            if (\count($websites) > 1) {
                sort($websites, SORT_NATURAL);

                foreach ($websites as $name) {
                    if ($name === $website) {
                        continue;
                    }

                    $websiteData = [
                        'link' => '?website=' . urlencode($name),
                        'title' => $name,
                    ];

                    $allWebsitesData[] = $websiteData;
                }

                $template['rightId'] = 'websites-column';
                $template['websites'] = $allWebsitesData;
            }
        }

        return $this->render('moderation.html', $template);
    }
}