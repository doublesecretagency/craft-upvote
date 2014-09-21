
// Load AJAX library
var ajax = window.superagent;

// Upvote JS object
var upvote = {
	// Cast an upvote
	upvote: function (elementId) {
		console.log('['+elementId+'] Cast upvote');
		this._vote(elementId, 'upvote');
	},
	// Cast a downvote
	downvote: function (elementId) {
		console.log('['+elementId+'] Cast downvote');
		this._vote(elementId, 'downvote');
	},
	// Cast vote
	_vote: function (id, vote) {
		// Set data
		var data = {'id':id};
		// Add the CSRF Token
		data[window.csrfTokenName] = window.csrfTokenValue;

		// Submit AJAX request
		ajax
			.post('/actions/upvote/'+vote)
			.send(data)
			.type('form')
			.set('X-Requested-With','XMLHttpRequest')
			.end(function (response) {
				console.log(JSON.parse(response.text));
			})
		;
	}
}