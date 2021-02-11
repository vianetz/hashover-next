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

abstract class AbstractHandler
{
    protected \HashOver $hashover;
    protected DataFiles $dataFiles;

    public function __construct(\HashOver $hashover, Locale $locale, DataFiles $dataFiles)
    {
        $this->hashover = $hashover;
        $this->hashover->setup->setsCookies = true;
        $this->hashover->locale = $locale;
        $this->dataFiles = $dataFiles;

        $this->checkAllowed();
    }

    // Redirects the user back to where they came from
    protected function redirect($url = '')
    {
        // Check if we're redirecting to a specific URL
        if (!empty ($url)) {
            // If so, use it
            header('Location: ' . $url);
        } else {
            // If not, check if there is a redirect specified
            if (!empty ($_GET['redirect'])) {
                // If so, use it
                header('Location: ' . $_GET['redirect']);
            } else {
                // If not, redirect to moderation
                header('Location: ../moderation/');
            }
        }

        // Exit after redirect
        exit;
    }

    // Parse and return template files
    protected function parse_templates($template, $fragment, array $data, \HashOver $hashover)
    {
        // Parse page fragment template file
        $page = $hashover->templater->parseTemplate(APP_DIR . '/templates/admin/' . $fragment, $data);

        // Indent page fragment by two tabs
        $page = str_replace(PHP_EOL, PHP_EOL . "\t\t", $page);

        // Get configured language in en-us format
        $language = str_replace('_', '-', strtolower($hashover->setup->language));

        // Fallback to English if documentation does not exist for configured language
        $language = file_exists('/docs/' . $language) ? $language : 'en-us';

        // Merge some default informatin into template data
        $data = array_merge($data, array(
            // HTTP root directory
            'root' => rtrim($hashover->setup->httpRoot, '/'),

            // HTTP admin root directory
            'admin' => $hashover->setup->getHttpPath('admin'),

            // Navigation locale strings
            'moderation' => $hashover->locale->text['moderation'],
            'ip-blocking' => $hashover->locale->text['block-ip-addresses'],
            'url-filtering' => $hashover->locale->text['filter-url-queries'],
            'settings' => $hashover->locale->text['settings'],
            'updates' => $hashover->locale->text['check-for-updates'],
            'docs' => $hashover->locale->text['documentation'],
            'logout' => $hashover->locale->text['logout'],

            // Configured language in en-us format
            'language' => $language,

            // Parsed page template
            'content' => $page
        ));

        // Check if form has been submitted
        if (!empty ($_GET['status'])) {
            // If so, check if form submission was successful
            if ($_GET['status'] === 'success') {
                // If so, add success message locale to template data
                $data['message'] = $hashover->locale->text['successful-save'];

                // And add message status to template data
                $data['message-status'] = 'success';
            } else {
                // If so, add error message locale to template data
                $data['message'] = $hashover->locale->text['failed-to-save'];

                // Add file permissions explanation to template data
                $data['error'] = $hashover->locale->permissionsInfo('config');

                // And add message status to template data
                $data['message-status'] = 'error';
            }
        }

        return $hashover->templater->parseTemplate(APP_DIR . '/templates/admin/' . $template . '.html', $data);
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
