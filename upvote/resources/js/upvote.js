// Load AJAX library
var ajax = window.superagent;

// Upvote JS object
var upvote = {
	// Default action trigger
	actionTrigger: 'actions',
	// Cast an upvote
	upvote: function (elementId, key) {
		if (this.devMode) {
			console.log('['+elementId+']'+(key ? ' ['+key+']' : '')+' Upvoting...');
		}
		this._vote(elementId, key, 'upvote');
	},
	// Cast a downvote
	downvote: function (elementId, key) {
		if (this.devMode) {
			console.log('['+elementId+']'+(key ? ' ['+key+']' : '')+' Downvoting...');
		}
		this._vote(elementId, key, 'downvote');
	},
	// Remove vote
	removeVote: function () {
		console.log('Vote removal is disabled.');
	},
	// Cast vote
	_vote: function (elementId, key, vote) {
		// Set vote icons
		var voteIcons = Sizzle('.upvote-'+vote+'-'+this._setItemKey(elementId, key));
		var voteMatch = this._determineMatch(voteIcons);
		// Set data
		var data = {
			'id': elementId,
			'key': key
		};
		data[window.csrfTokenName] = window.csrfTokenValue; // Append CSRF Token
		// If matching vote has not been cast
		if (!voteMatch) {

			// TODO: If downvoting is disabled, "opposites" are irrelevant

			// Define opposite
			var opposite;
			switch (vote) {
				case 'upvote': opposite = 'downvote'; break;
				case 'downvote': opposite = 'upvote'; break;
			}
			// Set opposite icons
			var oppositeIcons = Sizzle('.upvote-'+opposite+'-'+this._setItemKey(elementId, key));
			var oppositeMatch = this._determineMatch(oppositeIcons);
			// If opposite vote has already been cast
			if (oppositeMatch) {
				// Swap vote
				var action = '/'+this.actionTrigger+'/upvote/swap';
			} else {
				// Cast new vote
				var action = '/'+this.actionTrigger+'/upvote/'+vote;
			}
			// Vote via AJAX
			ajax
				.post(action)
				.send(data)
				.type('form')
				.set('X-Requested-With','XMLHttpRequest')
				.end(function (response) {
					var results = JSON.parse(response.text);
					if (upvote.devMode) {
						console.log('['+elementId+']'+(key ? ' ['+key+']' : '')+' Successfully cast '+vote);
						console.log(results);
					}
					var errorReturned = (typeof results == 'string' || results instanceof String);
					// If no error message was returned
					if (!errorReturned) {
						// If swapping vote
						if (oppositeMatch) {
							results.vote = results.vote * 2;
							upvote._removeMatchClass(oppositeIcons);
						}
						// Update tally & add class
						upvote._updateTally(elementId, key, results.vote);
						upvote._addMatchClass(voteIcons);
					}
				})
			;
		} else {
			// Unvote
			upvote.removeVote(elementId, key);
		}
	},
	// Update tally
	_updateTally: function (elementId, key, vote) {
		var tallies = Sizzle('.upvote-tally-'+this._setItemKey(elementId, key));
		for (var i = 0; i < tallies.length; i++) {
			tallies[i].textContent = parseInt(tallies[i].textContent) + parseInt(vote);
		}
	},
	// Generate combined item key
	_setItemKey: function (elementId, key) {
		return elementId+(key ? '-'+key : '');
	},
	// Determine whether matching vote has already been cast
	_determineMatch: function (icons) {
		if (!icons.length) {
			return false;
		} else {
			return ((' '+icons[0].className+' ').indexOf(' upvote-vote-match ') > -1);
		}
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