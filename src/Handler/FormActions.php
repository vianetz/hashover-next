<?php
declare(strict_types=1);

// Copyright (C) 2015-2019 Jacob Barkdull
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

use HashOver\FormData;
use HashOver\Helper\RequestHelper;
use HashOver\Misc;
use HashOver\WriteComments;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FormActions extends Javascript
{
    private \HashOver $hashover;
    private ResponseInterface $response;
    private WriteComments $writeComments;
    private FormData $formData;
    private RequestHelper $requestHelper;

    public function __construct(
        ResponseInterface $response,
        \HashOver $hashover,
        WriteComments $writeComments,
        FormData $formData,
        RequestHelper $requestHelper
    ) {
        $this->response = $response;
        $this->hashover = $hashover;
        $this->writeComments = $writeComments;
        $this->formData = $formData;
        $this->requestHelper = $requestHelper;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->setNonCache($this->response);
        $response = $this->setContentType($request, $response);

        $this->hashover->setMode(\HashOver::HASHOVER_MODE_JSON);

        // Throw exception if requested by remote server
        $this->hashover->setup->refererCheck();

        // Set page URL from POST/GET data
        $this->hashover->setup->setPageURL($request);

        // Set page title from POST/GET data
        $this->hashover->setup->setPageTitle('request');

        // Set thread name from POST/GET data
        $this->hashover->setup->setThreadName('request');

        if ($this->requestHelper->hasPostOrGet($request, 'login')) {
            $this->hashover->login->setLogin();
            $this->formData->displayMessage('logged-in');
        }

        if ($this->requestHelper->hasPostOrGet($request, 'logout')) {
            $this->hashover->login->clearLogin();
            $this->formData->displayMessage('logged-out');
        }

        $this->hashover->initiate();
        $this->hashover->finalize();

        $mode = $this->formData->viaAJAX ? 'javascript' : 'php';

        if ($this->requestHelper->hasPostOrGet($request, 'post')) {
            $this->formData->checkForSpam($mode);

            $data = $this->writeComments->postComment();

            $this->hashover->defaultMetadata();

            if (empty($data)) {
                $this->formData->displayMessage('post-fail');
            }

            $response->getBody()->write($this->displayJson($data));
            return $response;
        }

        if ($this->requestHelper->hasPostOrGet($request, 'edit')) {
            // Check IP address for spam
            $this->formData->checkForSpam($mode);

            $data = $this->writeComments->editComment();

            // Check if edited comment saved successfully
            if (! empty($data)) {
                echo $this->displayJson($data);
            } else {
                $this->formData->displayMessage('post-fail');
            }
        }

        if ($this->requestHelper->hasPostOrGet($request, 'delete')) {
            // Check IP address for spam
            $this->formData->checkForSpam($mode);

            $deleted = $this->writeComments->deleteComment();

            return $deleted ? $this->formData->displayMessage('comment-deleted') : $this->formData->displayMessage('post-fail');
        }
    }

    /**
     * Converts a file name (1-2) to a permalink (hashover-c1r1)
     */
    private function file_permalink($file)
    {
        return 'hashover-c' . str_replace('-', 'r', $file);
    }

    /**
     * Handles posted comment data
     */
    private function displayJson($data)
    {
        if (! $this->formData->viaAJAX) {
            $permalink = $this->file_permalink($data['file']);

            return $this->formData->kickback($permalink);
        }

        // Otherwise, split file into parts
        $key_parts = explode('-', $data['file']);

        $parsed = $this->hashover->commentParser->parse(
            $data['comment'], $data['file'], $key_parts
        );

        return Misc::jsonData(['count' => $this->hashover->getCommentCount(), 'comment' => $parsed]);
    }
}
