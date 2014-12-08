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
		console.log('Vote removal is disabled.');
	},
	// Cast vote
	_vote: function (elementId, vote) {
		// Set vote icons
		var voteIcons = Sizzle('.upvote-'+vote+'-'+elementId);
		var voteMatch = this._determineMatch(voteIcons);
		// Set data
		var data = {'id':elementId};
		data[window.csrfTokenName] = window.csrfTokenValue; // Append CSRF Token
		// If matching vote has not been cast
		if (!voteMatch) {
			// Define opposite
			var opposite;
			switch (vote) {
				case 'upvote': opposite = 'downvote'; break;
				case 'downvote': opposite = 'upvote'; break;
			}
			// Set opposite icons
			var oppositeIcons = Sizzle('.upvote-'+opposite+'-'+elementId);
			var oppositeMatch = this._determineMatch(oppositeIcons);
			// If opposite vote has already been cast
			if (oppositeMatch) {
				// Swap vote
				var action = '/actions/upvote/swap';
			} else {
				// Cast new vote
				var action = '/actions/upvote/'+vote;
			}
			// Vote via AJAX
			ajax
				.post(action)
				.send(data)
				.type('form')
				.set('X-Requested-With','XMLHttpRequest')
				.end(function (response) {
					var results = JSON.parse(response.text);
					console.log(results);
					var errorReturned = (typeof results == 'string' || results instanceof String);
					// If no error message was returned
					if (!errorReturned) {
						// If swapping vote
						if (oppositeMatch) {
							results.vote = results.vote * 2;
							upvote._removeMatchClass(oppositeIcons);
						}
						// Update tally & add class
						upvote._updateTally(elementId, results.vote);
						upvote._addMatchClass(voteIcons);
					}
				})
			;
		} else {
			// Unvote
			upvote.removeVote(elementId);
		}
	},
	// Update tally
	_updateTally: function (elementId, vote) {
		var tallies = Sizzle('.upvote-tally-'+elementId);
		for (var i = 0; i < tallies.length; i++) {
			tallies[i].textContent = parseInt(tallies[i].textContent) + parseInt(vote);
		}
	},
	// Determine whether matching vote has already been cast
	_determineMatch: function (icons) {
		return ((' '+icons[0].className+' ').indexOf(' upvote-vote-match ') > -1);
	},
	// Add vote match class to icons
	_addMatchClass: function (icons) {
		for (var i = 0; i < icons.length; i++) {
			icons[i].className += ' upvote-vote-match';
		}
	},
	// Remove vote match class from icons
	_removeMatchClass: function (icons) {
		for (var i = 0; i < icons.length; i++) {
			icons[i].className = icons[i].className.replace('upvote-vote-match', '');
		}
	}
}