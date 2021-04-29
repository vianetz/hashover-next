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

namespace HashOver\Handler\Api;

use HashOver\Handler\NonCacheable;
use HashOver\Misc;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @todo use latte template for rss (https://latte.nette.org/en/tips-tricks)
 */
final class Rss extends NonCacheable
{
    private \HashOver $hashover;
    private ResponseInterface $response;

    public function __construct(
        ResponseInterface $response,
        \HashOver $hashover
    ) {
        $this->response = $response;
        $this->hashover = $hashover;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->response->withAddedHeader('Content-Type', 'application/xml; charset=utf-8');
        $response = $this->setNonCache($response);

        $this->hashover->setMode(\HashOver::HASHOVER_MODE_RSS);

        try {
            // Throw exception if API is disabled
            $this->hashover->setup->apiCheck('rss');
            $this->hashover->setup->setPageURL($request);

            $this->hashover->initiate();

            // Parse primary comments into usable data
            $this->hashover->parsePrimary();

            $method = $this->hashover->setup->getRequest('sorting', 'by-date');
            $this->hashover->sortPrimary($method);

            $this->createRssFeed();
        } catch (\Exception $error) {
            $response->getBody()->write('<?xml version="1.0" encoding="UTF-8"?><error>' . $error->getMessage() . '</error>');
        }

        return $response;
    }

    private function createRssFeed(): void
    {
        $thread = $this->hashover->setup->threadName;

        // Attempt to read page information metadata
        $metadata = $this->hashover->thread->data->readMeta('page-info', $thread);

        // Check if metadata read successfully
        if ($metadata !== false) {
            // If so, set page URL blank if it's missing from the metadata
            if (!isset ($metadata['url'])) {
                $metadata['url'] = '';
            }

            // And set page title to "Untitled" if it's missing from the metadata
            if (!isset ($metadata['title'])) {
                $metadata['title'] = $this->hashover->locale->text['untitled'];
            }
        } else {
            // If not, set default metadata information
            $metadata = array(
                'url' => '',
                'title' => $this->hashover->locale->text['untitled']
            );
        }

        // Create new DOM document.
        $xml = new \DOMDocument ('1.0', 'UTF-8');
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        // Create main RSS element
        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');

        // Create channel element
        $channel = $xml->createElement('channel');

        // Create channel title element
        $title = $xml->createElement('title');
        $title_value = $xml->createTextNode(html_entity_decode($metadata['title'], ENT_COMPAT, 'UTF-8'));
        $title->appendChild($title_value);

        // Add channel title to channel element
        $channel->appendChild($title);

        // Create channel link element
        $link = $xml->createElement('link');
        $link_value = $xml->createTextNode(html_entity_decode($metadata['url'], ENT_COMPAT, 'UTF-8'));
        $link->appendChild($link_value);

        // Add channel link to channel element
        $channel->appendChild($link);

        // Check if there is more than one comment
        if ($this->hashover->thread->totalCount !== 1) {
            $showing_comments_locale = $this->hashover->locale->text['showing-comments'];
        } else {
            $showing_comments_locale = $this->hashover->locale->text['showing-comment'];
        }

        // Create channel description element
        $description = $xml->createElement('description');
        $count_locale = sprintf($showing_comments_locale, $this->hashover->thread->totalCount - 1);
        $description_value = $xml->createTextNode($count_locale);
        $description->appendChild($description_value);

        // Add channel description to channel element
        $channel->appendChild($description);

        // Create channel atom link element
        $atom_link = $xml->createElement('atom:link');
        $atom_url = $this->hashover->setup->domain . $_SERVER['PHP_SELF'] . '?url=' . urlencode($metadata['url']);
        $atom_link->setAttribute('href', 'http://' . $atom_url);
        $atom_link->setAttribute('rel', 'self');

        // Add channel atom link to channel element
        $channel->appendChild($atom_link);

        // Create channel language element
        $language = $xml->createElement('language');
        $language_value = $xml->createTextNode($this->hashover->setup->language);
        $language->appendChild($language_value);

        // Add channel language to channel element
        $channel->appendChild($language);

        // Create channel ttl element
        $ttl = $xml->createElement('ttl');
        $ttl_value = $xml->createTextNode('40');
        $ttl->appendChild($ttl_value);

        // Add channel ttl to channel element
        $channel->appendChild($ttl);

        // Add channel element to main RSS element
        $rss->appendChild($channel);

        // Add item element to main RSS element
        foreach ($this->hashover->comments['primary'] as $comment) {
            $this->parseCommentsIntoRssItem($metadata, $comment, $rss, $xml);
        }

        $xml->appendChild($rss);

        // Return RSS XML
        echo preg_replace_callback('/^(\s+)/m', function ($spaces) {
            return str_repeat("\t", mb_strlen($spaces[1]) / 2);
        }, $xml->saveXML());

        if ($this->hashover->setup->enableStatistics) {
            echo $this->hashover->statistics->executionEnd('php');
        }
    }

    private function parseCommentsIntoRssItem(&$metadata, &$comment, &$rss, &$xml): void
    {
        // Skip deleted/unmoderated comments
        if (isset($comment['notice'])) {
            return;
        }

        $comment['body'] = htmlentities($comment['body'], ENT_COMPAT, 'UTF-8', true);
        $comment['body'] = html_entity_decode($comment['body'], ENT_COMPAT, 'UTF-8');
        $comment['body'] = preg_replace('/\[(img|\/img)\]/iS', '', $comment['body']);
        $comment['body'] = $this->hashover->markdown->parseMarkdown($comment['body']);

        // Convert <code> tags to <pre> tags
        $comment['body'] = preg_replace('/(<|<\/)code>/iS', '\\1pre>', $comment['body']);

        // Get name from comment or use configured default
        $name = Misc::getArrayItem($comment, 'name') ?: $this->hashover->setup->defaultName;

        // Create item element
        $item = $xml->createElement('item');

        // Start item summary title with user's name
        $title = $name . ' : ';

        // Strip HTML tags from comment and remove all newlines
        $single_comment = str_replace(PHP_EOL, ' ', strip_tags($comment['body']));

        // Check if comment is more than 40 characters long
        if (mb_strlen($single_comment) > 40) {
            // If so, add 40 characters of comment to summary title
            $title .= mb_substr($single_comment, 0, 40) . '...';
        } else {
            // If not, add comment to summary title as-is
            $title .= $single_comment;
        }

        // Create item title element
        $item_title = $xml->createElement('title');
        $item_title_value = $xml->createTextNode(html_entity_decode($title, ENT_COMPAT, 'UTF-8'));
        $item_title->appendChild($item_title_value);

        // Add item title element to item element
        $item->appendChild($item_title);

        // Create item name element
        $item_name = $xml->createElement('name');
        $item_name_value = $xml->createTextNode(html_entity_decode($name, ENT_COMPAT, 'UTF-8'));
        $item_name->appendChild($item_name_value);

        // Add item name element to item element
        $item->appendChild($item_name);

        // URL regular expression
        $url_regex = '/((http|https|ftp):\/\/[a-z0-9-@:%_\+.~#?&\/=]+) {0,}/iS';

        // Add HTML anchor tag to URLs (hyperlinks)
        $comment['body'] = preg_replace($url_regex, '<a href="\\1" target="_blank">\\1</a>', $comment['body']);

        // Replace newlines with break tags
        $comment['body'] = str_replace(PHP_EOL, '<br>', $comment['body']);

        // Create item description element
        $item_description = $xml->createElement('description');
        $item_description_value = $xml->createTextNode($comment['body']);
        $item_description->appendChild($item_description_value);

        // Add item description element to item element
        $item->appendChild($item_description);

        // Create item avatar element
        if (! empty($comment['avatar'])) {
            $item_avatar = $xml->createElement('avatar');
            $item_avatar_value = $xml->createTextNode($comment['avatar']);
            $item_avatar->appendChild($item_avatar_value);

            // Add item avatar element to item element
            $item->appendChild($item_avatar);
        }

        // Check if likes are enabled
        if (!empty ($comment['likes'])) {
            // If so, create item likes element
            $item_likes = $xml->createElement('likes');
            $item_likes_value = $xml->createTextNode($comment['likes']);
            $item_likes->appendChild($item_likes_value);

            // Add item likes element to item element
            $item->appendChild($item_likes);
        }

        // Check if dislikes are enabled
        if ($this->hashover->setup->allowsDislikes === true) {
            // If so, check if comment has any dislikes
            if (!empty ($comment['dislikes'])) {
                // If so, create dislikes item element
                $item_dislikes = $xml->createElement('dislikes');
                $item_dislikes_value = $xml->createTextNode($comment['dislikes']);
                $item_dislikes->appendChild($item_dislikes_value);

                // Add dislikes item element to item element
                $item->appendChild($item_dislikes);
            }
        }

        // Create item publication date element
        $item_pubDate = $xml->createElement('pubDate');
        $item_pubDate_value = date(DATE_RSS, $comment['timestamp']);
        $item_pubDate_node = $xml->createTextNode($item_pubDate_value);
        $item_pubDate->appendChild($item_pubDate_node);

        // Add item pubDate element to item element
        $item->appendChild($item_pubDate);

        // URL to comment for item guide and link elements
        $item_permalink_url = $metadata['url'] . '#' . $comment['permalink'];

        // Create item guide element
        $item_guid = $xml->createElement('guid');
        $item_guid_value = $xml->createTextNode($item_permalink_url);
        $item_guid->appendChild($item_guid_value);

        // Add item guide element to item element
        $item->appendChild($item_guid);

        // Create item link element
        $item_link = $xml->createElement('link');
        $item_link_value = $xml->createTextNode($item_permalink_url);
        $item_link->appendChild($item_link_value);

        // Add item link element to item element
        $item->appendChild($item_link);

        // Add item element to main RSS element
        $rss->appendChild($item);

        // Recursively parse replies
        if (!empty ($comment['replies'])) {
            foreach ($comment['replies'] as $reply) {
                $this->parseCommentsIntoRssItem($metadata, $reply, $rss, $xml);
            }
        }
    }
}