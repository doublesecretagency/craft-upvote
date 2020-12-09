---
description: As of v2.1.0, you can cast a vote on behalf of a specific user (via PHP).
---

# Cast a vote on behalf of a specific user

```php
// Cast a new vote
Upvote::$plugin->upvote_vote->castVote($elementId, $key, $vote [, $userId = null]);

// Remove an existing vote
Upvote::$plugin->upvote_vote->removeVote($elementId, $key [, $userId = null]);
```

If the `$userId` is omitted, the vote will be cast by the currently logged-in user.

## Switching a vote to its opposite

In order to "swap" votes, you'll need to first remove the existing vote before applying it's opposing vote.

```php
// Attempt to remove vote
$response = Upvote::$plugin->upvote_vote->removeVote($elementId, $key, $userId);

// If message is returned, bail
if (!is_array($response)) {
    return $response;
}

// Cast new (opposing) vote
return Upvote::$plugin->upvote_vote->castVote($elementId, $key, $response['antivote'], $userId);
```

When the original vote is removed, the "antivote" will be deduced. You can then re-vote in the opposite direction, by specifying the antivote value.
