
// Load AJAX library
var ajax = window.superagent;

// Upvote JS object
var upvote = {
	// Cast an upvote
	upvote: function (elementId) {
		console.log('['+elementId+'] Upvote');
		this._vote(elementId, 'upvote');
	},
	// Cast a downvote
	downvote: function (elementId) {
		console.log('['+elementId+'] Downvote');
		this._vote(elementId, 'downvote');
	},
	// Remove vote
	removeVote: function () {
		console.log('Vote retraction is disabled.');
	},
	// Cast vote
	_vote: function (elementId, vote) {
		// Set icon element
		var icon = document.getElementById('upvote-'+vote+'-'+elementId);
		var voteMatch = ((' '+icon.className+' ').indexOf(' upvote-vote-match ') > -1);
		// Set data
		var data = {'id':elementId};
		data[window.csrfTokenName] = window.csrfTokenValue; // Append CSRF Token
		// If no vote match
		if (!voteMatch) {
			// Cast vote
			ajax
				.post('/actions/upvote/'+vote)
				.send(data)
				.type('form')
				.set('X-Requested-With','XMLHttpRequest')
				.end(function (response) {
					var results = JSON.parse(response.text);
					console.log(results);
					var errorReturned = (typeof results == 'string' || results instanceof String);
					// If no error message was returned
					if (!errorReturned) {
						upvote._updateScore(elementId, results.vote);
						icon.className += ' upvote-vote-match';
					}
				})
			;
		} else {
			// Unvote
			this.removeVote(elementId);
		}
	},
	// Update score
	_updateScore: function (elementId, vote) {
		var score = document.getElementById('upvote-score-'+elementId);
		if (score) {
			score.textContent = parseInt(score.textContent) + parseInt(vote);
		}
	}
}