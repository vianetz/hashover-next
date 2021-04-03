<?php namespace HashOver;

// Copyright (C) 2010-2019 Jacob Barkdull
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

use HashOver\Backend\SendNotification;
use HashOver\Domain\UserInputException;

class WriteComments extends Secrets
{
	protected $setup;
	protected $formData;
	protected $thread;

	protected $locale;
	protected $login;
	protected $cookies;
	protected $crypto;
	protected $avatar;
	protected $templater;
	private SendNotification $sendNotification;

	protected $name = '';
	protected $password = '';
	protected $loginHash = '';
	protected $email = '';
	protected $website = '';

	protected $data = array ();
	protected $urls = array ();

	// Characters to search for and replace with in comments
	protected $dataSearch = array (
		'\\',
		'"',
		'<',
		'>',
		"\r\n",
		"\r",
		"\n",
		'  '
	);

	// Character replacements
	protected $dataReplace = array (
		'&#92;',
		'&quot;',
		'&lt;',
		'&gt;',
		PHP_EOL,
		PHP_EOL,
		PHP_EOL,
		'&nbsp; '
	);

	// HTML tags to allow in comments
	protected $htmlTagSearch = array (
		'b',
		'big',
		'blockquote',
		'code',
		'em',
		'i',
		'li',
		'ol',
		'pre',
		's',
		'small',
		'strong',
		'sub',
		'sup',
		'u',
		'ul'
	);

	// HTML tags to automatically close
	public $closeTags = array (
		'b',
		'big',
		'blockquote',
		'em',
		'i',
		'li',
		'ol',
		'pre',
		's',
		'small',
		'strong',
		'sub',
		'sup',
		'u',
		'ul'
	);

	// Unprotected fields to update when editing a comment
	protected $editableFields = array (
		'body',
		'name',
		'email',
		'encryption',
		'email_hash',
		'notifications',
		'website'
	);

	// Password protected fields
	protected $protectedFields = array (
		'password',
		'login_id'
	);

	// Possible comment status options
	protected $statuses = array (
		'approved',
		'pending',
		'deleted'
	);

	public function __construct(
	    SendNotification $sendNotification,
        Setup $setup,
        FormData $form_data,
        Thread $thread,
        Locale $locale,
        Login $login,
        Cookies $cookies,
        Crypto $crypto
    ) {
	    parent::__construct();

		$this->setup = $setup;
		$this->formData = $form_data;
		$this->thread = $thread;
		$this->locale = $locale;
		$this->login = $login;
		$this->cookies = $cookies;
		$this->crypto = $crypto;
		$this->sendNotification = $sendNotification;

		// Setup initial login data
		$this->setupLogin ();
	}

	// Confirms attempted actions are to existing comments
	protected function verifyFile ($file)
	{
		// Attempt to get file
		$comment_file = $this->setup->getRequest ($file);

		// Check if file is set
		if ($comment_file !== false) {
			// Cast file to string
			$comment_file = (string)($comment_file);

			// Return true if POST file is in comment list
			if (in_array ($comment_file, $this->thread->commentList, true)) {
				return $comment_file;
			}

			// Set cookies to indicate failure
			if ($this->formData->viaAJAX !== true) {
				$this->cookies->setFailedOn ('comment', $this->formData->replyTo, false);
			}
		}

		throw new UserInputException($this->locale->text['comment-needed']);
	}

	// Encodes HTML entities
	protected function encodeHTML ($value)
	{
		return htmlentities ($value, ENT_COMPAT, 'UTF-8', false);
	}

	// Sets up necessary login data
	protected function setupLogin ()
	{
		$this->name = $this->encodeHTML ($this->login->name);
		$this->password = $this->encodeHTML ($this->login->password);
		$this->loginHash = $this->encodeHTML ($this->login->loginHash);
		$this->email = $this->encodeHTML ($this->login->email);
		$this->website = $this->encodeHTML ($this->login->website);
	}

	// User comment authentication
	protected function commentAuthentication ()
	{
		// Verify file exists
		$file = $this->verifyFile ('file');

		// Read original comment
		$comment = $this->thread->data->read ($file);

		// Authentication data
		$auth = array (
			// Assume no authorization by default
			'authorized' => false,
			'user-owned' => false,

			// Original comment
			'comment' => $comment
		);

		// Return authorization data if we fail to get comment
		if ($comment === false) {
			return $auth;
		}

		// Check if we have both required passwords
		if (!empty ($this->formData->data['password']) and !empty ($comment['password'])) {
			// If so, get the user input password
			$password = $this->encodeHTML ($this->formData->data['password']);

			// Get the comment password
			$comment_password = $comment['password'];

			// Attempt to compare the two passwords
			$match = $this->crypto->verifyHash ($password, $comment_password);

			// Authenticate if the passwords match
			if ($match === true) {
				$auth['user-owned'] = true;
				$auth['authorized'] = true;
			}
		}

		// Admin is always authorized after strict verification
		if ($this->login->verifyAdmin ($this->password) === true) {
			$auth['authorized'] = true;
		}

		// And return authorization data
		return $auth;
	}

	// Deletes comment
	public function deleteComment ()
	{
		// Check login requirements
		$this->login->checkRequirements ('You must be logged in to delete a comment!');

		// Authenticate user password
		$auth = $this->commentAuthentication ();

		// Check if user is authorized
		if ($auth['authorized'] === true) {
			// If so, strictly verify admin login
			$user_is_admin = $this->login->verifyAdmin ($this->password);

			// Unlink comment file indicator
			$unlink = ($user_is_admin or $this->setup->unlinksFiles === true);

			// Attempt to delete comment file
			$deleted = $this->thread->data->delete ($this->formData->file, $unlink);

			// Check if comment file was deleted successfully
			if ($deleted === true) {
				// If so, remove comment from latest comments metadata
				$this->thread->data->removeFromLatest ($this->formData->file);

				// And return true
				return true;
			}
		}

		// Otherwise, sleep for 5 seconds
		sleep (5);

		// And return false
		return false;
	}

	// Closes all allowed HTML tags
	public function tagCloser ($tags, $html)
	{
		// Run through tags
		for ($tc = 0, $tcl = count ($tags); $tc < $tcl; $tc++) {
			// Count opening and closing tags
			$open_tags = mb_substr_count ($html, '<' . $tags[$tc] . '>');
			$close_tags = mb_substr_count ($html, '</' . $tags[$tc] . '>');

			// Check if opening and closing tags aren't equal
			if ($open_tags !== $close_tags) {
				// Add closing tags to end of comment
				while ($open_tags > $close_tags) {
					$html .= '</' . $tags[$tc] . '>';
					$close_tags++;
				}

				// Remove closing tags for unopened tags
				while ($close_tags > $open_tags) {
					$html = preg_replace ('/<\/' . $tags[$tc] . '>/iS', '', $html, 1);
					$close_tags--;
				}
			}
		}

		// And return HTML with closed tags
		return $html;
	}

	// Extracts URLs for later injection
	protected function urlExtractor ($groups)
	{
		$link_number = count ($this->urls);
		$this->urls[] = $groups[1];

		return 'URL[' . $link_number . ']';
	}

	// Escapes all HTML tags excluding allowed tags
	public function htmlSelectiveEscape ($code)
	{
		// Escape all HTML tags
		$code = str_ireplace ($this->dataSearch, $this->dataReplace, $code);

		// Unescape allowed HTML tags
		foreach ($this->htmlTagSearch as $tag) {
			// Create search array of escaped opening and closing tags
			$escaped_tags = array ('&lt;' . $tag . '&gt;', '&lt;/' . $tag . '&gt;');

			// Create search array of opening and closing tags
			$text_tags = array ('<' . $tag . '>', '</' . $tag . '>');

			// Unescape opening and closed tags
			$code = str_ireplace ($escaped_tags, $text_tags, $code);
		}

		// And return new HTML
		return $code;
	}

	// Escapes HTML inside of <code> tags and markdown code blocks
	protected function codeEscaper ($groups)
	{
		return $groups[1] . htmlspecialchars ($groups[2], null, null, false) . $groups[3];
	}

	// Sets up and tests for necessary comment data
	protected function setupCommentData ($editing = false)
	{
		// Post fails when comment is empty
		if (empty ($this->formData->data['comment'])) {
			// Set cookies to indicate failure
			if ($this->formData->viaAJAX !== true) {
				$this->cookies->setFailedOn ('comment', $this->formData->replyTo);
			}

			// Throw exception about reply requirement
			if (!empty ($this->formData->replyTo)) {
				throw new \Exception (
					$this->locale->text['reply-needed']
				);
			}

			// Throw exception about comment requirement
			throw new \Exception (
				$this->locale->text['comment-needed']
			);
		}

		// Strictly verify if the user is logged in as admin
		if ($this->login->verifyAdmin ($this->password) === true) {
			// If so, check if status is set in POST data is set
			if (!empty ($this->formData->data['status'])) {
				// If so, use status if it is allowed
				if (in_array ($this->formData->data['status'], $this->statuses, true)) {
					$this->data['status'] = $this->formData->data['status'];
				}
			}
		} else {
			// Check if setup is for a comment edit
			if ($editing === true) {
				// If so, pend comment if edit moderation is enabled
				if ($this->setup->pendsUserEdits === true) {
					$this->data['status'] = 'pending';
				}
			} else {
				// If not, pend comment if moderation is enabled
				if ($this->setup->usesModeration === true) {
					$this->data['status'] = 'pending';
				}
			}
		}

		// Check if setup is for a comment edit
		if ($editing === true) {
			// If so, mimic normal user login
			$this->login->prepareCredentials ();
			$this->login->updateCredentials ();
		} else {
			// If not, setup initial login information
			if ($this->login->userIsLoggedIn !== true) {
				$this->login->setCredentials ();
			}
		}

		// Check if required fields have values
		$this->login->validateFields ();

		// Setup login data
		$this->setupLogin ();

		// Trim leading and trailing white space
		$clean_code = $this->formData->data['comment'];

		// URL regular expression
		$url_regex = '/((http|https|ftp):\/\/[a-z0-9-@:;%_\+.~#?&\/=]+)/i';

		// Extract URLs from comment
		$clean_code = preg_replace_callback ($url_regex, [$this, 'urlExtractor'], $clean_code);

		// Escape all HTML tags excluding allowed tags
		$clean_code = $this->htmlSelectiveEscape ($clean_code);

		// Collapse multiple newlines to three maximum
		$clean_code = preg_replace ('/' . PHP_EOL . '{3,}/', str_repeat (PHP_EOL, 3), $clean_code);

		// Close <code> tags
		$clean_code = $this->tagCloser (array ('code'), $clean_code);

		// Escape HTML inside of <code> tags and markdown code blocks
		$clean_code = preg_replace_callback ('/(<code>)(.*?)(<\/code>)/is', [$this, 'codeEscaper'], $clean_code);
		$clean_code = preg_replace_callback ('/(```)(.*?)(```)/is', [$this, 'codeEscaper'], $clean_code);

		// Close remaining tags
		$clean_code = $this->tagCloser ($this->closeTags, $clean_code);

		// Inject original URLs back into comment
		$clean_code = preg_replace_callback ('/URL\[([0-9]+)\]/', function ($groups) {
			$url_key = $groups[1];
			$url = $this->urls[$url_key];

			return $url . ' ';
		}, $clean_code);

		// Store clean code
		$this->data['body'] = $clean_code;

		// Store posting date
		$this->data['date'] = date (DATE_ISO8601);

		// Store name if one is given
		if ($this->setup->nameField !== 'off') {
			if (!empty ($this->name)) {
				$this->data['name'] = $this->name;
			}
		}

		// Store password and login ID if a password is given
		if ($this->setup->passwordField !== 'off') {
			if (!empty ($this->password)) {
				$this->data['password'] = $this->password;
			}
		}

		// Store login ID if login hash is non-empty
		if (!empty ($this->loginHash)) {
			$this->data['login_id'] = $this->loginHash;
		}

		// Check if the e-mail field is enabled
		if ($this->setup->emailField !== 'off') {
			// Check if we have an e-mail address
			if (!empty ($this->email)) {
				// Get encryption info for e-mail
				$encryption_keys = $this->crypto->encrypt ($this->email);

				// Set encrypted e-mail address
				$this->data['email'] = $encryption_keys['encrypted'];

				// Set decryption keys
				$this->data['encryption'] = $encryption_keys['keys'];

				// Set e-mail hash
				$this->data['email_hash'] = md5 (mb_strtolower ($this->email));

				// Get subscription status
				$subscribed = $this->setup->getRequest ('subscribe') ? 'yes' : 'no';

				// And set e-mail subscription if one is given
				$this->data['notifications'] = $subscribed;
			}
		}

		// Store website URL if one is given
		if ($this->setup->websiteField !== 'off') {
			if (!empty ($this->website)) {
				$this->data['website'] = $this->website;
			}
		}

		// Store user IP address if setup to and one is given
		if ($this->setup->storesIpAddress === true) {
			// Check if remote IP address exists
			if (!empty ($_SERVER['REMOTE_ADDR'])) {
				// If so, get XSS safe IP address
				$ip = Misc::makeXSSsafe ($_SERVER['REMOTE_ADDR']);

				// And set the IP address
				$this->data['ipaddr'] = $ip;
			}
		}
	}

	// Edits a comment
	public function editComment ()
	{
		// Check login requirements
		$this->login->checkRequirements ('You must be logged in to edit a comment!');

		// Authenticate user password
		$auth = $this->commentAuthentication ();

		// Check if user is authorized
		if ($auth['authorized'] === true) {
			// Set initial fields for update
			$update_fields = $this->editableFields;

			// Get file from form data
			$file = $this->formData->file;

			// Setup necessary comment data
			$this->setupCommentData (true);

			// Add status to editable fields if a new status is set
			if (!empty ($this->data['status'])) {
				$update_fields[] = 'status';
			}

			// Only set protected fields for update if passwords match
			if ($auth['user-owned'] === true) {
				$update_fields = array_merge ($update_fields, $this->protectedFields);
			}

			// Run through update fields
			foreach ($update_fields as $key) {
				// Check if field exists
				if (!empty ($this->data[$key])) {
					// If so, update comment data
					$auth['comment'][$key] = $this->data[$key];
				} else {
					// If not, remove it from comment data
					unset ($auth['comment'][$key]);
				}
			}

			// Attempt to write edited comment
			$saved = $this->thread->data->save ($file, $auth['comment'], true);

			// Check if edited comment saved successfully
			if ($saved === true) {
				// If so, return comment information
				return array (
					// Comment filename
					'file' => $file,

					// Comment data
					'comment' => $auth['comment']
				);
			}
		}

		// Otherwise, sleep for 5 seconds
		sleep (5);

		// And return empty array
		return array ();
	}

	protected function writeComment ($comment_file)
	{
		// Attempt to save comment
		$saved = $this->thread->data->save ($comment_file, $this->data);

		if ($saved) {
			// If so, add it to latest comments metadata
			$this->thread->data->addLatestComment ($comment_file);

			$this->sendNotification->send($comment_file, $this->data, $this->email, $this->formData->replyTo, $this->name);

			// Set/update user login cookie
			if ($this->setup->usesAutoLogin !== false) {
				if ($this->login->userIsLoggedIn !== true) {
					$this->login->setLogin ();
				}
			}

			// Increase comment count(s) if request is AJAX
			if ($this->formData->viaAJAX === true) {
				$this->thread->countComment ($comment_file);
			}

			// And return comment information
			return array (
				// Comment filename
				'file' => $comment_file,

				// Comment data
				'comment' => $this->data
			);
		}

		// Otherwise, return empty array
		return array ();
	}

	// Posts a comment
	public function postComment ()
	{
		// Check login requirements
		$this->login->checkRequirements ('You must be logged in to comment!');

		// Test for necessary comment data
		$this->setupCommentData ();

		// Set comment file name
		if (isset ($this->formData->replyTo)) {
			// Verify file exists
			$this->verifyFile ('reply-to');

			// Comment number
			$comment_number = $this->thread->threadCount[$this->formData->replyTo];

			// Rename file for reply
			$comment_file = $this->formData->replyTo . '-' . $comment_number;
		} else {
			$comment_file = (string)($this->thread->primaryCount);
		}

		// Check if comment thread exists
		$this->thread->data->checkThread ();

		// Write comment file
		$status = $this->writeComment ($comment_file);

		// And return result of comment file write
		return $status;
	}
}
