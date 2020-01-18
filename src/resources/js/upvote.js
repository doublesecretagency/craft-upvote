// Load AJAX library
var ajax = window.superagent;

// Upvote JS object
var upvote = {
    // Default action url
    actionUrl: '/actions/',
    // No CSRF token by default
    csrfToken: false,
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
    // Submit AJAX with fresh CSRF token
    _csrf: function (callback) {
        // Make object available to callback
        var that = this;
        // Fetch a new CSRF token
        ajax
            .get(this.actionUrl+'upvote/csrf')
            .end(function(err, res){
                // If something went wrong, bail
                if (!res.ok) {
                    console.log('Error retrieving CSRF token:', err);
                    return;
                }
                // Set global CSRF token
                that.csrfToken = res.body;
                // Run callback
                callback();
            })
        ;
    },
    // Cast vote
    _vote: function (elementId, key, vote) {
        // Make object available to callback
        var that = this;
        // Callback function for casting a vote
        var castVote = function () {
            // Initialize data with CSRF token
            var data = JSON.parse(JSON.stringify(that.csrfToken));
            // Set data
            data['id'] = elementId;
            data['key'] = key;
            // Set vote icons
            var voteIcons = Sizzle('.upvote-'+vote+'-'+that._setItemKey(elementId, key));
            var voteMatch = that._determineMatch(voteIcons);
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
                var oppositeIcons = Sizzle('.upvote-'+opposite+'-'+that._setItemKey(elementId, key));
                var oppositeMatch = that._determineMatch(oppositeIcons);
                // If opposite vote has already been cast
                if (oppositeMatch) {
                    // Swap vote
                    var action = that.actionUrl+'upvote/vote/swap';
                } else {
                    // Cast new vote
                    var action = that.actionUrl+'upvote/vote/'+vote;
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
                        var errorReturned = (typeof results === 'string' || results instanceof String);
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
        };
        // If token already exists
        if (this.csrfToken) {
            // Cast vote using existing token
            castVote();
        } else {
            // Cast vote using a fresh token
            this._csrf(castVote);
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
};
