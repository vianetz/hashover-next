<?php
declare(strict_types=1);

// Copyright (C) 2019 Jacob Barkdull
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

use HashOver\Crypto;
use HashOver\Misc;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CommentInfo extends Javascript
{
    private \HashOver $hashover;
    private ResponseInterface $response;
    private Crypto $crypto;

    public function __construct(ResponseInterface $response, \HashOver $hashover, Crypto $crypto)
    {
        $this->response = $response;
        $this->hashover = $hashover;
        $this->crypto = $crypto;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->setNonCache($this->response);
        $response = $this->setContentType($request, $response);

        $this->hashover->setMode(\HashOver::HASHOVER_MODE_JSON);

        $this->hashover->setup->setPageURL($request);
        $this->hashover->initiate();
        $data = $this->getJsonResponse();

        $response->getBody()->write(Misc::jsonData($data));
        return $response;
    }

    /**
     * Returns comment data or authentication error
     */
    private function getJsonResponse()
    {
        // Initial JSON data
        $data = array();

        // Get comment from POST/GET data
        $key = $this->hashover->setup->getRequest('comment', null);

        // Return error if we're missing necessary post data
        if ($key === null) {
            return array('error' => 'Missing comment file.');
        }

        // Sanitize file path
        $file = str_replace('../', '', $key);

        // Store references to some long variables
        $thread = $this->hashover->setup->threadName;

        $comment = $this->hashover->thread->data->read($file, $thread);

        // Return error message if failed to read comment
        if ($comment === false) {
            return array('error' => 'Failed to read file: "' . $file . '"');
        }

        $authorized = false;

        // Check if user is logged in
        if ($this->hashover->login->userIsLoggedIn === true) {
            // If so, user is authorized if they own the comment
            if (!empty ($comment['login_id'])) {
                if ($this->hashover->login->loginHash === $comment['login_id']) {
                    $authorized = true;
                }
            }

            // Or, user is authorized if they are Admin
            if ($this->hashover->login->isAdmin() === true) {
                $authorized = true;
            }
        }

        // Check if user is authorized to receive comment data
        if ($authorized === true) {
            $crypto = $this->crypto;

            $data = array(
                // Commenter name
                'name' => Misc::getArrayItem($comment, 'name') ?: '',

                // Commenter website URL
                'website' => Misc::getArrayItem($comment, 'website') ?: '',

                // Commenter's comment
                'body' => Misc::getArrayItem($comment, 'body') ?: ''
            );

            // Add decrypted email address to data if an email exists
            if (!empty ($comment['email']) and !empty ($comment['encryption'])) {
                $data ['email'] = $crypto->decrypt($comment['email'], $comment['encryption']);
            }

            // And return comment data
            return $data;
        }

        sleep(5);

        return ['error' => $this->hashover->locale->text['post-fail']];
    }
}
