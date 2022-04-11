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

    /**
     * @var null|int Element ID of the element being voted on.
     */
    public ?int $id;

    /**
     * @var null|string Optional key for allowing multiple vote types.
     */
    public ?string $key;

    /**
     * @var null|string Combination of `id` and optional `key`.
     */
    public ?string $itemKey;

    /**
     * @var null|int ID of the user casting a vote (if login is required to vote).
     */
    public ?int $userId;

    /**
     * @var null|int The user's vote. Positive one is an upvote, negative one is a downvote.
     */
    public ?int $userVote;

    /**
     * @var null|bool Whether the event was triggered by a vote removal.
     */
    public ?bool $isAntivote;

    /**
     * @var null|int The combined value of all votes. (Upvotes - Downvotes)
     */
    public ?int $tally;

    /**
     * @var null|int The combined total number of votes. (Upvotes + Downvotes)
     */
    public ?int $totalVotes;

    /**
     * @var null|int The total number of Upvotes cast.
     */
    public ?int $totalUpvotes;

    /**
     * @var null|int The total number of Downvotes cast.
     */
    public ?int $totalDownvotes;

    // ========================================================================= //
    // DEPRECATED: REMOVE IN NEXT MAJOR VERSION

    /**
     * @var null|int Value of vote cast.
     * @deprecated in 2.1.0. Use `userVote` instead.
     */
    public ?int $vote;

    /**
     * @var null|int Opposing value of removed vote.
     * @deprecated in 2.1.0. Use `isAntivote` and `userVote` instead.
     */
    public ?int $antivote;

}
