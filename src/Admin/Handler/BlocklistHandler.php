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

use HashOver\HTMLTag;
use HashOver\Misc;

final class BlocklistHandler extends AbstractHandler
{
    public function run(): void
    {
        $blocklist = array();

        // Blocklist JSON file location
        $blocklist_file = $this->hashover->setup->getAbsolutePath('config/blocklist.json');

        // Check if the form has been submitted
        if (!empty ($_POST['addresses']) and is_array($_POST['addresses'])) {
            // If so, run through submitted addresses
            foreach ($_POST['addresses'] as $address) {
                // Add each non-empty address value to the blocklist array
                if (!empty ($address)) {
                    $blocklist[] = $address;
                }
            }

            // Check if the user login is admin
            if ($this->hashover->login->verifyAdmin() === true) {
                // If so, attempt to save the JSON data
                $saved = $this->dataFiles->saveJSON($blocklist_file, $blocklist);

                // If saved successfully, redirect with success indicator
                if ($saved === true) {
                    $this->redirect('./?status=success');
                }
            }

            $this->redirect('./?status=failure');
        }

        $json = $this->dataFiles->readJSON($blocklist_file);

        // Check for JSON parse error
        if (is_array($json)) {
            $blocklist = $json;
        }

        // IP Address inputs
        $inputs = new HTMLTag('span');

        // Create IP address inputs
        for ($i = 0, $il = max(3, count($blocklist)); $i < $il; $i++) {
            // Create input tag
            $input = new HTMLTag ('input', array(
                'class' => 'addresses',
                'type' => 'text',
                'name' => 'addresses[]',
                'value' => Misc::getArrayItem($blocklist, $i) ?: '',
                'size' => '15',
                'maxlength' => '15',
                'placeholder' => '127.0.0.1',
                'title' => $this->hashover->locale->text['blocklist-ip-tip'],
            ), false, true);

            $inputs->appendChild($input);
        }

        $template = [
            'title' => $this->hashover->locale->text['blocklist-title'],
            'sub-title' => $this->hashover->locale->text['blocklist-sub'],
            'inputs' => $inputs->getInnerHTML("\t\t"),
            'save-button' => $this->hashover->locale->text['save'],
        ];

        echo $this->parse_templates('admin', 'blocklist.html', $template, $this->hashover);
    }
}
