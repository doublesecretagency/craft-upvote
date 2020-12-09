---
description: Upvote events follow the same basic pattern as standard Craft events.
---

# Events

Upvote events follow the same pattern as [standard Craft events.](https://craftcms.com/docs/3.x/extend/updating-plugins.html#events)

There are two events which are raised when a new vote is cast...

```php
use doublesecretagency\upvote\Upvote;
use doublesecretagency\upvote\events\VoteEvent;
use yii\base\Event;

// Do something BEFORE a vote is cast...
Event::on(
    Upvote::class,
    Upvote::EVENT_BEFORE_VOTE,
    function (VoteEvent $event) {
        // See the complete list of parameters below
        $elementId = $event->id;
    }
);

// Do something AFTER a vote is cast...
Event::on(
    Upvote::class,
    Upvote::EVENT_AFTER_VOTE,
    function (VoteEvent $event) {
        // See the complete list of parameters below
        $elementId = $event->id;
    }
);
```

There are also two events which are raised then a vote is removed (or swapped)...

```php
use doublesecretagency\upvote\Upvote;
use doublesecretagency\upvote\events\VoteEvent;
use yii\base\Event;

// Do something BEFORE a vote is removed...
Event::on(
    Upvote::class,
    Upvote::EVENT_BEFORE_UNVOTE,
    function (VoteEvent $event) {
        // See the complete list of parameters below
        $elementId = $event->id;
    }
);

// Do something AFTER a vote is removed...
Event::on(
    Upvote::class,
    Upvote::EVENT_AFTER_UNVOTE,
    function (VoteEvent $event) {
        // See the complete list of parameters below
        $elementId = $event->id;
    }
);
```

:::warning When are events triggered?
 - When a user **casts** an upvote or downvote, a `VOTE` event is triggered.
 - When a user **removes** an existing vote, an `UNVOTE` (aka "antivote") event is triggered.
 - When a user **switches** to the opposing vote, it will first `UNVOTE` the original before then casting a new `VOTE`.
:::

All events provide the same set of parameters:

| Parameter        | Type     | Description
|:-----------------|:---------|-------------
| `id`             | _int_    | Element ID of the element being voted on.
| `key`            | _string_ or _null_ | Optional key for allowing [multiple vote types](/multiple-voting-for-the-same-element/).
| `itemKey`        | _string_ | Combination of `id` and optional `key`.
| `userId`         | _int_    | ID of the user casting a vote (if login is required to vote).
| `userVote`       | `1`&nbsp;or&nbsp;`-1` | The user's vote. Positive one is an upvote, negative one is a downvote.
| `isAntivote`     | _bool_   | Was the event triggered by a vote removal?
| `tally`          | _int_    | Combined **value** of all votes. (Upvotes - Downvotes)
| `totalVotes`     | _int_    | Combined **total number** of votes. (Upvotes + Downvotes)
| `totalUpvotes`   | _int_    | Total number of Upvotes cast.
| `totalDownvotes` | _int_    | Total number of Downvotes cast.

:::warning Minor changes in Upvote 2.1
The parameters of each event have been adjusted slightly in v2.1.
:::

## Determine vote removal

You can check whether the vote was a removal (aka "unvote") by checking the `isAntivote` value. If true, the vote is an antivote, which means two things...

 - `isAntivote` will be true. It will be false when a normal vote is cast.
 - `userVote` will have a value that is the exact opposite of the vote which was removed. So if the original vote was a `1`, the antivote would be `-1` (and vice versa).
