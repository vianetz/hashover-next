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

final class ModerationHandler extends AbstractHandler
{
    private function add_table_row(HTMLTag $body, HTMLTag $child)
    {
        // Create table row and column
        $tr = new HTMLTag('tr');
        $td = new HTMLTag('td');

        // Append child element to column
        $td->appendChild($child);

        // Append column to row
        $tr->appendChild($td);

        // Append row to table body
        $body->appendChild($tr);
    }

    private function add_table_head(HTMLTag $table, $html)
    {
        // Create table head, row and column
        $thead = new HTMLTag ('thead');
        $th = new HTMLTag ('tr', false, false);
        $td = new HTMLTag ('th', false, false);

        // Append child element to column
        $td->innerHTML($html);

        // Append column to row
        $th->appendChild($td);

        // Append row to head
        $thead->appendChild($th);

        // Append row to table body
        $table->appendChild($thead);
    }

    public function __invoke(): void
    {
        // Get current website
        $current_website = $this->hashover->setup->website;

        // Attempt to get website from GET data
        $website = $this->hashover->setup->getRequest('website', $current_website);

        // Set website if GET website is different
        if ($website !== $current_website) {
            $this->hashover->setup->setWebsite($website);
        }

        // Attempt to get array of comment threads
        $threads = $this->hashover->thread->queryThreads();

        // Create comment thread table
        $threads_table = new HTMLTag ('table', array(
            'id' => 'threads',
            'cellspacing' => '0',
            'cellpadding' => '8'
        ));

        // Create comment thread table body
        $threads_body = new HTMLTag ('tbody');

        // Add table head if multiple website support is enabled
        if ($this->hashover->setup->supportsMultisites === true) {
            $this->add_table_head($threads_table, $website);
        }

        // Run through comment threads
        foreach ($threads as $thread) {
            // Read and parse JSON metadata file
            $data = $this->hashover->thread->data->readMeta('page-info', $thread);

            // Check if metadata was read successfully
            if ($data === false or empty ($data['url']) or empty ($data['title'])) {
                continue;
            }

            // Create row div
            $div = new HTMLTag ('div');

            // Add thread hyperlink to row div
            $div->appendChild(new HTMLTag ('a', array(
                'href' => 'threads/?' . implode('&amp;', array(
                        'website=' . urlencode($website),
                        'thread=' . urlencode($thread),
                        'title=' . urlencode($data['title']),
                        'url=' . urlencode($data['url'])
                    )),

                'innerHTML' => $data['title']
            )));

            // Add thread URL line to row div
            $div->appendChild(new HTMLTag ('p', array(
                'children' => array(new HTMLTag ('small', $data['url'], false))
            )));

            // And row div to table body
            $this->add_table_row($threads_body, $div);
        }

        // Add table body to table
        $threads_table->appendChild($threads_body);

        // Template data
        $template = array(
            'title' => $this->hashover->locale->text['moderation'],
            'sub-title' => $this->hashover->locale->text['moderation-sub'],
            'left-id' => 'threads-column',
            'threads' => $threads_table->asHTML("\t\t\t\t"),
        );

        // Check if multiple website support is enabled
        if ($this->hashover->setup->supportsMultisites === true) {
            // If so, attempt to get array of websites
            $websites = $this->hashover->thread->queryWebsites();

            // Add domain to array of websites if it isn't present
            if (!in_array($this->hashover->setup->domain, $websites)) {
                $websites[] = $this->hashover->setup->domain;
            }

            // Check if other websites exist
            if (count($websites) > 1) {
                // If so, create comment thread table
                $websites_table = new HTMLTag ('table', array(
                    'id' => 'websites',
                    'cellspacing' => '0',
                    'cellpadding' => '8'
                ));

                // Add table head
                $this->add_table_head($websites_table, $this->hashover->locale->text['websites']);

                // Sort the websites
                sort($websites, SORT_NATURAL);

                // Run through website directories
                foreach ($websites as $name) {
                    // Skip current website
                    if ($name === $website) {
                        continue;
                    }

                    // Create website hyperlink
                    $this->add_table_row($websites_table, new HTMLTag ('a', array(
                        'href' => '?website=' . urlencode($name),
                        'innerHTML' => $name
                    ), false));
                }

                // And add other websites to template
                $template = array_merge($template, array(
                    'right-id' => 'websites-column',
                    'websites' => $websites_table->asHTML("\t\t\t\t")
                ));
            }
        }

        echo $this->parse_templates('admin', 'moderation.html', $template, $this->hashover);
    }
}