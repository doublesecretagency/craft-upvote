// This script is only available if "Allow vote removal" is checked

// Extend upvote object to allow vote removal
upvote.removeVote = function (elementId, key) {
    // If setup is not complete, bail
    if (!upvote.setupComplete) {
        return;
    }
    // Callback function for casting a vote
    var removeVote = function () {
        // Initialize data with CSRF token
        var data = JSON.parse(JSON.stringify(upvote.csrfToken));
        // Set data
        data['id'] = elementId;
        data['key'] = key;
        // Remove vote
        ajax
            .post(upvote.actionUrl+'upvote/vote/remove')
            .send(data)
            .type('form')
            .set('X-Requested-With','XMLHttpRequest')
            .end(function (response) {
                var entry = JSON.parse(response.text);
                // If error was returned, log and bail
                if (typeof entry === 'string') {
                    console.log(prefix+' '+entry);
                    return;
                }
                // Update values & remove classes
                upvote._setAllValues(entry);
                upvote._removeVoteClass(entry.id, entry.key, 'upvote');
                upvote._removeVoteClass(entry.id, entry.key, 'downvote');
            })
        ;
    };
    // If token already exists
    if (upvote.csrfToken) {
        // Cast vote using existing token
        removeVote();
    } else {
        // Cast vote using a fresh token
        upvote._csrf(removeVote);
    }
};

// Extend upvote object to allow vote removal
upvote._removeVoteClass = function (elementId, key, vote) {
    var icons = Sizzle('.upvote-'+vote+'-'+upvote._setItemKey(elementId, key));
    upvote._removeMatchClass(icons);
};
