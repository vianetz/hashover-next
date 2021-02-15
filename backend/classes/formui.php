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

use HashOver\Helper\TemplateHelper;

class FormUI
{
	protected $mode;
	protected $setup;
	public $commentCounts;

	protected $locale;
	protected $avatars;
	protected $misc;
	protected $login;
	protected $cookies;
	protected $pageTitle;
	protected $pageURL;

	protected $emphasizedField;
	protected $defaultLoginInputs;

	public $postComment;
	public $popularComments;
	public $comments;

	private TemplateHelper $templateHelper;

	public function __construct ($mode = 'javascript', Setup $setup, array $counts)
	{
	    $this->templateHelper = new TemplateHelper($setup);

		// Store parameters as properties
		$this->mode = $mode;
		$this->setup = $setup;
		$this->commentCounts = $counts;

		// Instantiate various classes
		$this->locale = new Locale ($setup);
		$this->login = new Login ($setup);
		$this->cookies = new Cookies ($setup, $this->login);
		$this->avatars = new Avatars ($setup);
		$this->pageTitle = $this->setup->pageTitle;
		$this->pageURL = $this->setup->pageURL;

		// Attempt to get form field submission failed on
		$failedField = $this->cookies->getValue ('failed-on');

		// Set the field to emphasize after a failed post
		if ($failedField !== null) {
			$this->emphasizedField = $failedField;
		}

		// "Post a comment" locale strings
		$this->postComment = $this->locale->text['post-a-comment'];

		// Use "Post a comment on <page title>" locale instead if configured to
		if ($this->setup->displaysTitle !== false and !empty ($this->pageTitle)) {
			$this->postComment = sprintf (
				$this->locale->text['post-a-comment-on'],
				$this->pageTitle
			);
		}

		// Create default login inputs elements
		$this->defaultLoginInputs = $this->loginInputs ();
	}

    /**
     * @deprecated
     */
	public function prefix($id = '', $template = true)
    {
        return $this->templateHelper->prefix($id, $template);
    }

	// Creates input elements for user login information
	protected function loginInputs ($permalink = '', $edit_form = false, $name = '', $email = '', $website = '')
	{
		// Reply/edit form indicator
		$is_form = !empty ($permalink);

		// Prepend dash to permalink if present
		$permalink = $is_form ? '-' . $permalink : '';

		// Login input attribute information
		$login_input_attributes = array (
			'name' => array (
				'wrapper-class'		=> 'hashover-name-input',
				'label-class'		=> 'hashover-name-label',
				'placeholder'		=> $this->locale->text['name'],
				'input-id'		=> $this->prefix ('main-name' . $permalink, $is_form),
				'input-type'		=> 'text',
				'input-name'		=> 'name',
				'input-title'		=> $this->locale->text['name-tip'],
				'input-value'		=> Misc::makeXSSsafe ($this->login->name),
				'autocomplete'		=> 'username'
			),

			'password' => array (
				'wrapper-class'		=> 'hashover-password-input',
				'label-class'		=> 'hashover-password-label',
				'placeholder'		=> $this->locale->text['password'],
				'input-id'		=> $this->prefix ('main-password' . $permalink, $is_form),
				'input-type'		=> 'password',
				'input-name'		=> 'password',
				'input-title'		=> $this->locale->text['password-tip'],
				'input-value'		=> '',
				'autocomplete'		=> 'current-password'
			),

			'email' => array (
				'wrapper-class'		=> 'hashover-email-input',
				'label-class'		=> 'hashover-email-label',
				'placeholder'		=> $this->locale->text['email'],
				'input-id'		=> $this->prefix ('main-email' . $permalink, $is_form),
				'input-type'		=> 'email',
				'input-name'		=> 'email',
				'input-title'		=> $this->locale->text['email-tip'],
				'input-value'		=> Misc::makeXSSsafe ($this->login->email),
				'autocomplete'		=> 'off'
			),

			'website' => array (
				'wrapper-class'		=> 'hashover-website-input',
				'label-class'		=> 'hashover-website-label',
				'placeholder'		=> $this->locale->text['website'],
				'input-id'		=> $this->prefix ('main-website' . $permalink, $is_form),
				'input-type'		=> 'url',
				'input-name'		=> 'website',
				'input-title'		=> $this->locale->text['website-tip'],
				'input-value'		=> Misc::makeXSSsafe ($this->login->website),
				'autocomplete'		=> 'off'
			)
		);

		// Change input values to specified values
		if ($edit_form === true) {
			$login_input_attributes['name']['input-value'] = $name;
			$login_input_attributes['password']['placeholder'] = $this->locale->text['confirm-password'];
			$login_input_attributes['password']['input-title'] = $this->locale->text['confirm-password'];
			$login_input_attributes['email']['input-value'] = $email;
			$login_input_attributes['website']['input-value'] = $website;
		}

		// Create wrapper element for styling login inputs
		$login_inputs = new HTMLTag ('div', array (
			'class' => 'hashover-inputs'
		));

		// Create and append login input elements to main form inputs wrapper element
		foreach ($login_input_attributes as $field => $attributes) {
			// Skip disabled input tags
			if ($this->setup->formFields[$field] === 'off') {
				continue;
			}

			// Create cell element for inputs
			$input_cell = new HTMLTag ('div', array (
				'class' => 'hashover-input-cell'
			));

			// Check if form labels are enabled
			if ($this->setup->usesLabels === true) {
				// If so, create label element for input
				$label = new HTMLTag ('label', array (
					'for' => $attributes['input-id'],
					'class' => $attributes['label-class'],
					'innerHTML' => $attributes['placeholder']
				), false);

				// Add label to cell element
				$input_cell->appendChild ($label);
			}

			// Add a class for indicating a required field
			if ($this->setup->formFields[$field] === 'required') {
				$input_cell->appendAttribute ('class', 'hashover-required-input');
			}

			// Create wrapper element for input
			$input_wrapper = new HTMLTag ('div', array (
				'class' => $attributes['wrapper-class']
			));

			// Add a class for indicating a post failure
			if ($this->emphasizedField === $field) {
				$input_wrapper->appendAttribute ('class', 'hashover-emphasized-input');
			}

			// Create input element
			$input = new HTMLTag ('input', array (
				'id' => $attributes['input-id'],
				'class' => 'hashover-input-info',
				'type' => $attributes['input-type'],
				'name' => $attributes['input-name'],
				'value' => $attributes['input-value'],
				'autocomplete' => $attributes['autocomplete'],
				'title' => $attributes['input-title'],
				'placeholder' => $attributes['placeholder'],
			), false, true);

			if ($this->setup->formFields[$field] === 'required') {
                $input->appendAttribute('required', 'required');
            }

			// Add input to wrapper element
			$input_wrapper->appendChild ($input);

			// Add input to cell element
			$input_cell->appendChild ($input_wrapper);

			// Add input cell to main inputs wrapper element
			$login_inputs->appendChild ($input_cell);
		}

		return $login_inputs;
	}

	// Creates avatar element
	protected function avatar ($text)
	{
		// If avatars set to images
		if ($this->setup->iconMode === 'image') {
			// Logged in
			if ($this->login->userIsLoggedIn === true) {
				// Image source is avatar image
				$hash = !empty ($this->login->email) ? md5 (mb_strtolower (trim ($this->login->email))) : '';
				$avatar_src = $this->avatars->getGravatar ($hash);
			} else {
				// Logged out
				// Image source is local default image
				$avatar_src = $this->setup->getImagePath ('first-comment');
			}

			// Create avatar image element
			$avatar = new HTMLTag ('div', array (
				'style' => 'background-image: url(\'' . $avatar_src . '\');'
			), false);
		} else {
			// Avatars set to count
			// Create element displaying comment number user will be
			$avatar = new HTMLTag ('span', $text, false);
		}

		return $avatar;
	}

	// Creates "Notify me of replies" checkbox
	protected function subscribeLabel ($permalink = '', $type = 'main', $checked = true)
	{
		// Reply/edit form indicator
		$is_form = !empty ($permalink);

		// The checkbox ID
		$id = $this->prefix ($type . '-subscribe', $is_form);

		// Append permalink to the ID if one was given
		if ($is_form === true) {
			$id .= '-' . $permalink;
		}

		// Create subscribe checkbox label element
		$subscribe_label = new HTMLTag ('label', array (
			'for' => $id,
			'class' => 'hashover-' . $type . '-label',
			'title' => $this->locale->text['subscribe-tip']
		));

		// Create subscribe element checkbox
		$subscribe = new HTMLTag ('input', array (
			'id' => $id,
			'type' => 'checkbox',
			'name' => 'subscribe'
		), false, true);

		// Tick the checkbox
		if ($checked === true) {
			$subscribe->createAttribute ('checked', 'true');
		}

		// Add subscribe checkbox element to subscribe checkbox label element
		$subscribe_label->appendChild ($subscribe);

		// Add text to subscribe checkbox label element
		$subscribe_label->appendInnerHTML ($this->locale->text['subscribe']);

		return $subscribe_label;
	}

	// Creates a table-like cell for the allowed HTML/markdown panel
	protected function formatCell ($format, $locale_key)
	{
		// Create cell title
		$title = new HTMLTag ('p', array ('class' => 'hashover-title'));

		// "Allowed HTML/Markdown" locale string
		$title->innerHTML ($this->locale->text['allowed-' . $format]);

		// Create allowed HTML/markdown text paragraph
		$paragraph = new HTMLTag ('p', array (
			'innerHTML' => $this->locale->text[$locale_key])
		);

		// And return both elements in a <div> tag
		return new HTMLTag ('div', array (
			'children' => array ($title, $paragraph)
		));
	}

    /**
     * Creats a comment form, ie. main/reply/edit
     *
     * @deprecated
     */
    protected function commentForm(HTMLTag $form, $type, $placeholder, $text, $permalink = '')
    {
        $isForm = ! empty($permalink);

        $permalink = $isForm ? '-' . $permalink : '';

        // Form title locale key
        $title_locale = ($type === 'reply') ? 'reply-form' : 'comment-form';

        // Create textarea
        $textarea = new HTMLTag ('textarea', array(
            'id' => $this->prefix($type . '-comment' . $permalink, $isForm),
            'class' => 'hashover-textarea hashover-' . $type . '-textarea',
            'cols' => '63',
            'name' => 'comment',
            'rows' => '6',
            'title' => $this->locale->text[$title_locale],
            'required' => 'required'
        ), false);

        // Set the placeholder attribute if one is given
        if (!empty ($placeholder)) {
            $textarea->createAttribute('placeholder', $placeholder);
        }

        if ($type === 'main') {
            // Add a class for indicating a post failure
            if ($this->emphasizedField === 'comment') {
                $textarea->appendAttribute('class', 'hashover-emphasized-input');
            }

            // If the comment was a reply, have the textarea use the reply textarea locale
            if ($this->cookies->getValue('replied') !== null) {
                $reply_form_placeholder = $this->locale->text['reply-form'];
                $textarea->createAttribute('placeholder', $reply_form_placeholder);
            }
        }

        // Set textarea content if given any text
        if (!empty ($text)) {
            $textarea->innerHTML($text);
        }

        // Add textarea element to form element
        $form->appendChild($textarea);

        // Create element for various messages when needed
        if ($type !== 'main') {
            $message = new HTMLTag ('div', array(
                'id' => $this->prefix($type . '-message-container' . $permalink, $isForm),
                'class' => 'hashover-message',

                'children' => array(
                    new HTMLTag ('div', array(
                        'id' => $this->prefix($type . '-message' . $permalink, $isForm)
                    ))
                )
            ));

            // Add message element to form element
            $form->appendChild($message);
        }

        // Create allowed HTML message element
        $allowed_formatting_message = new HTMLTag ('div', array(
            'id' => $this->prefix($type . '-formatting-message' . $permalink, $isForm),
            'class' => 'hashover-formatting-message'
        ));

        // Create formatting table
        $allowed_formatting_table = new HTMLTag ('div', array(
            'class' => 'hashover-formatting-table',

            'children' => array(
                $this->formatCell('html', 'html-format')
            )
        ));

        // Append Markdown cell if Markdown is enabled
        if ($this->setup->usesMarkdown !== false) {
            $markdown_cell = $this->formatCell('markdown', 'markdown-format');
            $allowed_formatting_table->appendChild($markdown_cell);
        }

        // Append formatting table to formatting message
        $allowed_formatting_message->appendChild($allowed_formatting_table);

        // Ensure the allowed HTML message is open in PHP mode
        if ($this->mode === 'php') {
            $allowed_formatting_message->appendAttribute('class', 'hashover-message-open');
            $allowed_formatting_message->appendAttribute('class', 'hashover-php-message-open');
        }

        // Add allowed HTML message element to form element
        $form->appendChild($allowed_formatting_message);
    }

	// Creates hidden page info fields, ie. page URL, title, reply comment
	protected function pageInfoFields (HTMLTag $form, $url = '[url]', $thread = '[thread]', $title = '[title]')
	{
		// Create hidden page URL input element
		$url_input = new HTMLTag ('input', array (
			'type' => 'hidden',
			'name' => 'url',
			'value' => $url
		), false, true);

		// Add hidden page URL input element to form element
		$form->appendChild ($url_input);

		// Create hidden comment thread input element
		$thread_input = new HTMLTag ('input', array (
			'type' => 'hidden',
			'name' => 'thread',
			'value' => $thread
		), false, true);

		// Add hidden comments thread input element to form element
		$form->appendChild ($thread_input);

		// Create hidden page title input element
		$title_input = new HTMLTag ('input', array (
			'type' => 'hidden',
			'name' => 'title',
			'value' => $title
		), false, true);

		// Add hidden page title input element to form element
		$form->appendChild ($title_input);

		// Check if the script is being remotely accessed
		if ($this->setup->remoteAccess === true) {
			// Create hidden input element indicating remote access
			$remote_access_input = new HTMLTag ('input', array (
				'type' => 'hidden',
				'name' => 'remote-access',
				'value' => 'true'
			), false, true);

			// Add remote access input element to form element
			$form->appendChild ($remote_access_input);
		}
	}

	// Creates "Formatting" link to open the allowed HTML/markdown panel
	protected function formatting ($type, $permalink = '')
	{
		// Reply/edit form indicator
		$is_form = !empty ($permalink);

		// Prepend dash to permalink if present
		$permalink = $is_form ? '-' . $permalink : '';

		// "Formatting" hyperlink locale
		$allowed_format = $this->locale->text['comment-formatting'];

		// Create allowed HTML message revealer hyperlink
		$allowed_formatting = new HTMLTag ('span', array (
			'id' => $this->prefix ($type . '-formatting' . $permalink, $is_form),
			'class' => 'hashover-fake-link hashover-formatting',
			'title' => $allowed_format,
			'innerHTML' => $allowed_format
		));

		// Return the hyperlink
		return $allowed_formatting;
	}
}
