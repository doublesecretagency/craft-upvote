
// This script is only available if "Allow vote removal" is checked

// Extend upvote object to allow vote removal
upvote.removeVote = function (elementId) {

	// Set data
	var data = {'id':elementId};
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
				upvote._updateTally(elementId, results.antivote);
				upvote._removeVoteClass(elementId, 'upvote');
				upvote._removeVoteClass(elementId, 'downvote');
			}
		})
	;
}

// Extend upvote object to allow vote removal
upvote._removeVoteClass = function (elementId, vote) {
	var icon = document.getElementById('upvote-'+vote+'-'+elementId);
	if (icon) {
		icon.className = icon.className.replace('upvote-vote-match', '');
	}
}