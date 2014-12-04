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
		// Set icon elements
		var icons = Sizzle('.upvote-'+vote+'-'+elementId);
		var voteMatch = ((' '+icons[0].className+' ').indexOf(' upvote-vote-match ') > -1);
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
						upvote._updateTally(elementId, results.vote);
						for (var i = 0; i < icons.length; i++) {
							icons[i].className += ' upvote-vote-match';
						}
					}
				})
			;
		} else {
			// Unvote
			this.removeVote(elementId);
		}
	},
	// Update tally
	_updateTally: function (elementId, vote) {
		var tallies = Sizzle('.upvote-tally-'+elementId);
		for (var i = 0; i < tallies.length; i++) {
			tallies[i].textContent = parseInt(tallies[i].textContent) + parseInt(vote);
		}
	}
}