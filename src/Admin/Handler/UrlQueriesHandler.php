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

final class UrlQueriesHandler extends AbstractHandler
{
    public function run(): void
    {
        // Default URL Query Pair array
        $ignored_queries = array();

        $queries_file = $this->hashover->setup->getAbsolutePath('config/ignored-queries.json');

        if (!empty ($_POST['names']) and is_array($_POST['names'])
            and !empty ($_POST['values']) and is_array($_POST['values'])) {
            // If so, run through submitted queries
            for ($i = 0, $il = count($_POST['names']); $i < $il; $i++) {
                // Add each non-empty query to the pair array
                if (!empty ($_POST['names'][$i])) {
                    // Start the query pair with the query name
                    $query_pair = $_POST['names'][$i];

                    // Check if the query has a value
                    if (!empty ($_POST['values'][$i])) {
                        // If so, add it to the pair
                        $query_pair .= '=' . $_POST['values'][$i];
                    }

                    // Add the query pair to the URL Query Pair array
                    $ignored_queries[] = $query_pair;
                }
            }

            if ($this->hashover->login->verifyAdmin()) {
                // If so, attempt to save the JSON data
                $saved = $this->dataFiles->saveJSON($queries_file, $ignored_queries);

                if ($saved) {
                    $this->redirect('./?status=success');
                }
            }

            $this->redirect('./?status=failure');
        }

        // Otherwise, load and parse URL Query Pairs file
        $json = $this->dataFiles->readJSON($queries_file);

        // Check for JSON parse error
        if (is_array($json)) {
            $ignored_queries = $json;
        }

        // URL Query Pair inputs
        $inputs = new HTMLTag('span');

        // Create URL Query Pair inputs
        for ($i = 0, $il = max(3, count($ignored_queries)); $i < $il; $i++) {
            // Use URL query pairs from file or blank
            $query = Misc::getArrayItem($ignored_queries, $i) ?: '';

            // Split query pair by equals sign
            $query_parts = explode('=', $query);

            // Create div tag for name and value inputs
            $input = new HTMLTag ('div');

            // Add input for query name to input div
            $input->appendChild(new HTMLTag ('input', array(
                'class' => 'name',
                'type' => 'text',
                'name' => 'names[]',
                'value' => $query_parts[0],
                'size' => '15',
                'placeholder' => $this->hashover->locale->text['name'],
                'title' => $this->hashover->locale->text['url-queries-name-tip']
            ), false, true));

            // Add input for query value to input div
            $input->appendChild(new HTMLTag ('input', array(
                'class' => 'value',
                'type' => 'text',
                'name' => 'values[]',
                'value' => Misc::getArrayItem($query_parts, 1) ?: '',
                'size' => '25',
                'placeholder' => $this->hashover->locale->text['value'],
                'title' => $this->hashover->locale->text['url-queries-value-tip']
            ), false, true));

            // Add input to inputs container
            $inputs->appendChild($input);
        }

        $template = [
            'title' => $this->hashover->locale->text['url-queries-title'],
            'sub-title' => $this->hashover->locale->text['url-queries-sub'],
            'inputs' => $inputs->getInnerHTML("\t\t"),
            'save-button' => $this->hashover->locale->text['save']
        ];

        // Load and parse HTML template
        echo $this->parse_templates('admin', 'url-queries.html', $template, $this->hashover);
    }
}
