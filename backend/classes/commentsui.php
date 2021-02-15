<?php namespace HashOver;

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


class CommentsUI extends FormUI
{
	// Creates a wrapper element for each comment
	public function commentWrapper ($permalink = '[permalink]')
	{
		$comment_wrapper = new HTMLTag ('div', array (
			'id' => $this->prefix ($permalink),
			'class' => 'hashover-comment'
		), false);

		if ($this->mode !== 'php') {
			$comment_wrapper->appendAttribute ('class', '[class]', false);
			$comment_wrapper->innerHTML ('[html]');

			return $comment_wrapper->asHTML ();
		}

		return $comment_wrapper;
	}

	// Creates wrapper element to name element
	public function nameWrapper ($class = '[class]', $link = '[link]')
	{
		$name_wrapper = new HTMLTag ('span', array (
			'class' => 'hashover-comment-name ' . $class,
			'innerHTML' => $link
		), false);

		return $name_wrapper->asHTML ();
	}

	// Creates name hyperlink/span element
	public function nameElement ($element, $permalink = '[permalink]', $name = '[name]', $href = '[href]')
	{
		// Decide what kind of element to create
		switch ($element) {
			case 'a': {
				// A hyperlink pointing to the user's input URL
				$name_link = new HTMLTag ('a', array (
					'rel' => 'noopener noreferrer nofollow',
					'href' => $href,
					'id' => $this->prefix ('name-' . $permalink),
					'target' => '_blank',
					'title' => $name,
					'innerHTML' => $name
				), false);

				break;
			}

			case 'span': {
				// A plain wrapper element
				$name_link = new HTMLTag ('span', array (
					'id' => $this->prefix ('name-' . $permalink),
					'innerHTML' => $name
				), false);

				break;
			}
		}

		return $name_link->asHTML ();
	}

	// Creates hyperlink with URL queries to link reference
	protected function queryLink ($href = false, array $queries = array ())
	{
		// Given hyperlink URL or default file path
		$href = $href ?: $this->setup->filePath;

		// Merge given URL queries with existing page URL queries
		$queries = array_merge ($this->setup->urlQueryList, $queries);

		// Add URL queries to path if URL has queries
		if (!empty ($queries)) {
			$href .= '?' . implode ('&', $queries);
		}

		// And create hyperlink
		$link = new HTMLTag ('a', array (
			'rel' => 'nofollow',
			'href' => $href
		), false);

		return $link;
	}

	// Creates "Top of Thread" hyperlink element
	public function parentThreadLink ($href = '[href]', $parent = '[parent]', $permalink = '[permalink]', $name = '[name]')
	{
		// Get locale string
		$thread_locale = $this->locale->text['thread'];

		// Inject OP's name into the locale
		$inner_html = sprintf ($thread_locale, $name);

		// Create hyperlink element
		$thread_link = $this->queryLink ($href);

		// Create hyperlink element
		$thread_link->appendAttributes (array (
			'rel' => 'nofollow',
			'href' => '#' . $parent,
			'id' => $this->prefix ('thread-link-' . $permalink),
			'class' => 'hashover-thread-link',
			'title' => $this->locale->text['thread-tip'],
			'innerHTML' => $inner_html
		), false);

		return $thread_link->asHTML ();
	}

	// Creates date/permalink hyperlink element
	public function dateLink ($href = '[href]', $permalink = '[permalink]', $title = '[title]', $date = '[date]')
	{
		// Create hyperlink element
		$date_link = $this->queryLink ($href);

		// Append more attributes
		$date_link->appendAttributes (array (
			'href' => '#' . $permalink,
			'class' => 'hashover-date-permalink',
			'title' => 'Permalink - ' . $title,
			'innerHTML' => $date
		), false);

		return $date_link->asHTML ();
	}

	// Creates element to hold a count of likes/dislikes each comment has
	public function likeCount ($type, $permalink = '[permalink]', $text = '[text]')
	{
		// Create element
		$count = new HTMLTag ('span', array (
			'id' => $this->prefix ($type . '-' . $permalink),
			'class' => 'hashover-' . $type,
			'innerHTML' => $text
		), false);

		return $count->asHTML ();
	}

	// Creates "Like"/"Dislike" hyperlink element
	public function likeLink ($type, $permalink = '[permalink]', $class = '[class]', $title = '[title]', $text = '[text]')
	{
		// Create hyperlink element
		$link = new HTMLTag ('a', array (
			'rel' => 'nofollow',
			'href' => '#',
			'id' => $this->prefix ($type . '-' . $permalink),
			'class' => $class,
			'title' => $title,
			'innerHTML' => $text
		), false);

		return $link->asHTML ();
	}

	// Creates a form control hyperlink element
	public function formLink ($href, $type, $permalink = '[permalink]', $class = '[class]', $title = '[title]')
	{
		// Form ID for hyperlinks
		$form = 'hashover-' . $type;

		// Create hyperlink element
		$link = $this->queryLink ($href, array ($form . '=' . $permalink));

		// "Reply to Comment" or "Edit Comment" locale key
		$title_locale = ($type === 'reply') ? 'reply-to-comment' : 'edit-your-comment';

		// Create more attributes
		$link->createAttributes (array (
			'id' => $this->prefix ($type. '-link-' . $permalink),
			'class' => 'hashover-comment-' . $type,
			'title' => $this->locale->text[$title_locale]
		));

		// Append href attribute
		$link->appendAttribute ('href', '#' . $form . '-' . $permalink, false);

		// Append attributes
		if ($type === 'reply') {
			$link->appendAttributes (array (
				'class' => $class,
				'title' => '- ' . $title
			));
		}

		// Add link text
		$link->innerHTML ($this->locale->text[$type]);

		return $link->asHTML ();
	}

	// Creates "Cancel" hyperlink element
	public function cancelLink ($permalink, $for, $class = '')
	{
		$cancel_link = $this->queryLink ($this->setup->filePath);
		$cancel_locale = $this->locale->text['cancel'];

		// Append href attribute
		$cancel_link->appendAttribute ('href', '#' . $permalink, false);

		// Create more attributes
		$cancel_link->createAttributes (array (
			'class' => 'hashover-comment-' . $for,
			'title' => $cancel_locale
		));

		// Append optional class
		if (!empty ($class)) {
			$cancel_link->appendAttribute ('class', $class);
		}

		// Add "Cancel" hyperlink text
		$cancel_link->innerHTML ($cancel_locale);

		return $cancel_link->asHTML ();
	}

	// Creates a user avatar image or comment number
	public function userAvatar ($src = '[src]', $href = '[href]', $text = '[text]')
	{
		// If avatars set to images
		if ($this->setup->iconMode !== 'none') {
			// Create wrapper element for avatar image
			$avatar_wrapper = new HTMLTag ('span', array (
				'class' => 'hashover-avatar'
			), false);

			if ($this->setup->iconMode !== 'count') {
				// Create avatar image element
				$comments_avatar = new HTMLTag ('div', array (
					'style' => 'background-image: url(\'' . $src . '\');'
				), false);
			} else {
				// Avatars set to count
				// Create element displaying comment number user will be
				$comments_avatar = new HTMLTag ('a', array (
					'rel' => 'nofollow',
					'href' => '#' . $href,
					'title' => 'Permalink',
					'innerHTML' => $text
				), false);
			}

			// Add comments avatar to avatar image wrapper element
			$avatar_wrapper->appendChild ($comments_avatar);

			return $avatar_wrapper->asHTML ();
		}

		return '';
	}


	// Creates thread hyperlink element
	public function threadLink ($url = '[url]', $title = '[title]')
	{
		// Create hyperlink element
		$thread_link = new HTMLTag ('a', array (
			'rel' => 'nofollow',
			'href' => $url,
			'innerHTML' => $title
		), false);

		return $thread_link->asHTML ();
	}
}
