// This script is only available if "Allow vote removal" is checked

// Extend upvote object to allow vote removal
upvote.removeVote = function (elementId, key) {
    // If setup is not complete, bail
    if (!this.setupComplete) {
        return;
    }
    // Make object available to callback
    var that = this;
    // Callback function for casting a vote
    var removeVote = function () {
        // Initialize data with CSRF token
        var data = JSON.parse(JSON.stringify(that.csrfToken));
        // Set data
        data['id'] = elementId;
        data['key'] = key;
        // Remove vote
        ajax
            .post(that.actionUrl+'upvote/vote/remove')
            .send(data)
            .type('form')
            .set('X-Requested-With','XMLHttpRequest')
            .end(function (response) {
                var results = JSON.parse(response.text);
                var errorReturned = (typeof results === 'string' || results instanceof String);
                // If no error message was returned
                if (!errorReturned) {
                    that._updateTally(elementId, key, results.antivote);
                    that._removeVoteClass(elementId, key, 'upvote');
                    that._removeVoteClass(elementId, key, 'downvote');
                }
            })
        ;
    };
    // If token already exists
    if (this.csrfToken) {
        // Cast vote using existing token
        removeVote();
    } else {
        // Cast vote using a fresh token
        this._csrf(removeVote);
    }
};

// Extend upvote object to allow vote removal
upvote._removeVoteClass = function (elementId, key, vote) {
    var icons = Sizzle('.upvote-'+vote+'-'+this._setItemKey(elementId, key));
    upvote._removeMatchClass(icons);
};
