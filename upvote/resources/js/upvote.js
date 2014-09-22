
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
				var success = JSON.parse(response.text);
				console.log(success);
				// If successful
				if (success) {
					// Change score in DOM
					var score = document.getElementById('upvote-score-'+id);
					score.textContent = parseInt(score.textContent) + parseInt(success.vote);
					// Mark voting icon
					var icon = document.getElementById('upvote-'+vote+'-'+id);
					icon.className += ' upvote-vote-match';
				}
			})
		;
	}
}