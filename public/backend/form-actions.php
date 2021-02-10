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

namespace HashOver;

use Dotenv\Dotenv;
use HashOver\Backend\SendNotification;
use Monolog\Logger;

if (isset($_GET['jsonp'])) {
    // If so, setup HashOver for JavaScript
    require __DIR__ . '/../../backend/javascript-setup.php';
    $request = $_GET;
} else {
    require __DIR__ . '/../../backend/json-setup.php';
    $request = $_POST;
}

// Converts a file name (1-2) to a permalink (hashover-c1r1)
function file_permalink($file)
{
    return 'hashover-c' . str_replace('-', 'r', $file);
}

// Handles posted comment data
function display_json(\HashOver $hashover, FormData $form_data, $data)
{
    // Check if request is HTTP
    if ($form_data->viaAJAX === false) {
        // If so, convert file to permalink
        $permalink = file_permalink($data['file']);

        // And redirect to comment
        return $form_data->kickback($permalink);
    }

    // Otherwise, split file into parts
    $key_parts = explode('-', $data['file']);

    // Parse comment data
    $parsed = $hashover->commentParser->parse(
        $data['comment'], $data['file'], $key_parts
    );

    // And return JSON or JSONP function call
    return Misc::jsonData(array(
                              // Current comment count
                              'count' => $hashover->getCommentCount(),

                              // Parsed comment data
                              'comment' => $parsed
                          ));
}

try {
    $hashover = new \HashOver('json');

    $dotenv = Dotenv::createImmutable(APP_DIR);
    $dotenv->load();

    // Throw exception if requested by remote server
    $hashover->setup->refererCheck();

    // Set page URL from POST/GET data
    $hashover->setup->setPageURL('request');

    // Set page title from POST/GET data
    $hashover->setup->setPageTitle('request');

    // Set thread name from POST/GET data
    $hashover->setup->setThreadName('request');

    // Instantiate FormData class
    $form_data = new FormData ($hashover->setup, $hashover->cookies);

    // Handle user login
    if (isset ($request['login'])) {
        // Log the user in
        $hashover->login->setLogin();

        // Kick visitor back if told to
        $form_data->displayMessage('logged-in');
    }

    // Handle user logout
    if (isset ($request['logout'])) {
        // Log the user out
        $hashover->login->clearLogin();

        // Kick visitor back
        $form_data->displayMessage('logged-out');
    }

    // Initiate and finalize comment processing
    $hashover->initiate();
    $hashover->finalize();

    $email = new Backend\EmailSender($container->get(Logger::class), $_ENV['SMTP_HOST'], (int)$_ENV['SMTP_PORT'], $_ENV['SMTP_USER'], $_ENV['SMTP_PASSWORD']);
    $sendNotification = new SendNotification(
        $email,
        $hashover->setup,
        new Crypto(),
        new Avatars($hashover->setup),
        new Templater($hashover->setup),
        new Thread($hashover->setup)
    );

    // Instantiate class for writing and editing comments
    $write_comments = new WriteComments(
        $sendNotification,
        $hashover->setup,
        $form_data,
        $hashover->thread
    );

    // Decide SPAM check mode
    $mode = $form_data->viaAJAX ? 'javascript' : 'php';

    // Handle new comment post
    if (isset ($request['post'])) {
        // Check IP address for spam
        $form_data->checkForSpam($mode);

        // Save posted comment
        $data = $write_comments->postComment();

        // Create/update page metadata
        $hashover->defaultMetadata();

        // Check if comment saved successfully
        if (!empty ($data)) {
            // If so, display comment as JSON data
            echo display_json($hashover, $form_data, $data);
        } else {
            // If not, redirect to failure message
            $form_data->displayMessage('post-fail');
        }
    }

    // Handle comment edit
    if (isset ($request['edit'])) {
        // Check IP address for spam
        $form_data->checkForSpam($mode);

        // Save edited comment
        $data = $write_comments->editComment();

        // Check if edited comment saved successfully
        if (!empty ($data)) {
            // If so, display comment as JSON data
            echo display_json($hashover, $form_data, $data);
        } else {
            // If not, redirect to failure message
            $form_data->displayMessage('post-fail');
        }
    }

    // Handle comment deletion
    if (isset ($request['delete'])) {
        // Check IP address for spam
        $form_data->checkForSpam($mode);

        // Attempt to delete comment
        $deleted = $write_comments->deleteComment();

        // Check if comment was deleted successfully
        if ($deleted === true) {
            // If so, redirect to deletion message
            $form_data->displayMessage('comment-deleted');
        } else {
            // If not, redirect to failure message
            $form_data->displayMessage('post-fail');
        }
    }
} catch (\Throwable $error) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get(\Monolog\Logger::class);
    $logger->error($error->getMessage() . ', ' . $error->getTraceAsString());
    echo Misc::displayError('An error occured.', 'json');
}
