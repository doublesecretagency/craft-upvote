<?php
/**
 * Upvote plugin for Craft CMS
 *
 * Lets your users upvote/downvote, "like", or favorite any type of element.
 *
 * @author    Double Secret Agency
 * @link      https://www.doublesecretagency.com/
 * @copyright Copyright (c) 2014 Double Secret Agency
 */

namespace doublesecretagency\upvote\events;

use yii\base\Event;

/**
 * Class VoteEvent
 * @since 2.0.0
 */
class VoteEvent extends Event
{

    /** @var int Element ID of the element being voted on. */
    public $id;

    /** @var string|null Optional key for allowing multiple vote types. */
    public $key;

    /** @var string Combination of `id` and optional `key`. */
    public $itemKey;

    /** @var int|null ID of the user casting a vote (if login is required to vote). */
    public $userId;

    /** @var int The user's vote. Positive one is an upvote, negative one is a downvote. */
    public $userVote;

    /** @var bool Whether the event was triggered by a vote removal. */
    public $isAntivote;

    /** @var int The combined value of all votes. (Upvotes - Downvotes) */
    public $tally;

    /** @var int The combined total number of votes. (Upvotes + Downvotes) */
    public $totalVotes;

    /** @var int The total number of Upvotes cast. */
    public $totalUpvotes;

    /** @var int The total number of Downvotes cast. */
    public $totalDownvotes;

    // DEPRECATED: REMOVE IN NEXT MAJOR VERSION

    /** @var int|null DEPRECATED: Value of vote cast. Use `userVote` instead. */
    public $vote;

    /** @var int|null DEPRECATED: Opposing value of removed vote. Use `isAntivote` and `userVote` instead. */
    public $antivote;

}
