// This script is only available if "Allow vote removal" is checked

// Extend upvote object to allow vote removal
upvote.removeVote = function (elementId, key) {
	// Set data
	var data = {
		'id': elementId,
		'key': key
	};
	data[window.csrfTokenName] = window.csrfTokenValue; // Append CSRF Token
	// Cast vote
	ajax
		.post('/actions/upvote/remove')
		.send(data)
		.type('form')
		.set('X-Requested-With','XMLHttpRequest')
		.end(function (response) {
			var results = JSON.parse(response.text);
			console.log(results);
			var errorReturned = (typeof results == 'string' || results instanceof String);
			// If no error message was returned
			if (!errorReturned) {
				upvote._updateTally(elementId, key, results.antivote);
				upvote._removeVoteClass(elementId, key, 'upvote');
				upvote._removeVoteClass(elementId, key, 'downvote');
			}
		})
	;
}

// Extend upvote object to allow vote removal
upvote._removeVoteClass = function (elementId, key, vote) {
	var icons = Sizzle('.upvote-'+vote+'-'+this._setItemKey(elementId, key));
	upvote._removeMatchClass(icons);
}