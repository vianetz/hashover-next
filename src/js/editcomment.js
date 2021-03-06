// Displays edit form (editcomment.js)
HashOver.prototype.editComment = function (comment, callback)
{
	// Do nothing if the comment isn't editable
	if (comment['editable'] !== true) {
		return false;
	}

	var hashover = this;
	var editInfo = HashOver.backendPath + '/comment-info';
	var permalink = comment.permalink;

	// Get file
	var file = this.permalinkFile (permalink);

	// Set request queries
	var queries = [
		'url=' + encodeURIComponent (this.instance['page-url']),
		'thread=' + encodeURIComponent (this.instance['thread-name']),
		'comment=' + encodeURIComponent (file)
	];

	// Get edit link element
	var link = this.getElement ('edit-link-' + permalink);

	// Set loading class to edit link
	this.classes.add (link, 'hashover-loading');

	// Send request for comment information
	this.ajax ('post', editInfo, queries, function (info) {
		// Check if request returned an error
		if (info.error !== undefined) {
			// If so, display error
			alert (info.error);

			// Remove loading class from edit link
			hashover.classes.remove (link, 'hashover-loading');

			// And do nothing else
			return;
		}

		// Get and clean comment body
		var body = info.body.replace (hashover.rx.links, '$1');

		// Get edit form placeholder
		var placeholder = hashover.getElement ('placeholder-edit-form-' + permalink);

		// Available comment status options
		var statuses = [ 'approved', 'pending', 'deleted' ];

		// Create edit form element
		var form = hashover.createElement ('form', {
			id: hashover.prefix ('edit-' + permalink),
			className: 'hashover-edit-form',
			action: hashover.setup['http-backend'] + '/form-actions',
			method: 'post'
		});

		// Place edit form fields into form
		form.innerHTML = hashover.strings.parseTemplate (
			hashover.ui['edit-form'], {
				hashover: hashover.prefix (),
				permalink: permalink,
				url: hashover.instance['page-url'],
				thread: hashover.instance['thread-name'],
				title: hashover.instance['page-title'],
				file: file,
				name: info.name || '',
				email: info.email || '',
				website: info.website || '',
				body: body
			}
		);

		// Prevent input submission
		hashover.preventSubmit (form);

		// Add edit form to placeholder
		placeholder.appendChild (form);

		// Set status dropdown menu option to comment status
		hashover.elementExists ('edit-status-' + permalink, function (status) {
			if (comment.status !== undefined) {
				status.selectedIndex = statuses.indexOf (comment.status);
			}
		});

		// Uncheck subscribe checkbox if user isn't subscribed
		hashover.elementExists ('edit-subscribe-' + permalink, function (sub) {
			if (comment.subscribed !== true) {
				sub.checked = null;
			}
		});

		// Get delete button
		var editDelete = hashover.getElement('edit-delete-' + permalink);

		// Get "Save Edit" button
		var saveEdit = hashover.getElement ('edit-post-' + permalink);

		// Change "Edit" link to "Cancel" link
		hashover.cancelSwitcher ('edit', link, placeholder, permalink);

		// Displays confirmation dialog for comment deletion
		editDelete.onclick = function () {
			if (confirm (hashover.locale['delete-comment'])) {
				editDelete.classList.add('clicked');
			}
		};

		// Attach click event to formatting revealer hyperlink
		hashover.formattingOnclick ('edit', permalink);

		form.addEventListener('submit', function (evt) {
			hashover.postComment(form, editDelete.classList.contains('clicked') ? editDelete : saveEdit, 'edit', permalink, link.onclick);
			evt.preventDefault();
		});

		// Remove loading class from edit link
		hashover.classes.remove (link, 'hashover-loading');

		// And execute callback if one was given
		if (typeof (callback) === 'function') {
			callback ();
		}
	}, true);

	// And return false
	return false;
};
