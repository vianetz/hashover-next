// For posting comments, both traditionally and via AJAX (postcomment.js)
HashOver.prototype.postComment = function (form, button, type, permalink, callback)
{
	// Disable button
	setTimeout (function () {
		button.disabled = true;
	}, 250);

	// Post by sending an AJAX request if enabled
	if (this.postRequest) {
		return this.postRequest.apply (this, arguments);
	}

	// Re-enable button after 10 seconds
	setTimeout (function () {
		button.disabled = false;
	}, 10000);

	return true;
};
