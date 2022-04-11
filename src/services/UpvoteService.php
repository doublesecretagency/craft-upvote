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

namespace doublesecretagency\upvote\services;

use Craft;
use craft\base\Component;
use craft\db\Query as CraftQuery;
use craft\elements\User;
use craft\helpers\Json;
use doublesecretagency\upvote\Upvote;

/**
 * Class UpvoteService
 * @since 2.0.0
 */
class UpvoteService extends Component
{

    /**
     * @var string Name of cookie containing the user history.
     */
    public string $userCookie = 'VoteHistory';

    /**
     * @var int Length of time until user history cookie expires.
     */
    public int $userCookieLifespan = 315569260; // Lasts 10 years

    /**
     * @var array History of anonymous user.
     */
    public array $anonymousHistory = [];

    /**
     * @var array History of logged-in User.
     */
    public array $loggedInHistory = [];

    /**
     * @var array Alias of current history type.
     */
    public array $history = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        // If login is required
        if (Upvote::$plugin->getSettings()->requireLogin) {
            // Rely on user history from DB
            $this->history =& $this->loggedInHistory;
        } else {
            // Rely on anonymous user history
            $this->history =& $this->anonymousHistory;
        }

        parent::init();
    }

    /**
     * Set history of logged-in user.
     */
    public function getUserHistory(): void
    {
        // If table has not been created yet, bail
        if (!Craft::$app->getDb()->tableExists('{{%upvote_userhistories}}')) {
            return;
        }

        // Get current user
        $currentUser = Craft::$app->user->getIdentity();

        // If no current user, bail
        if (!$currentUser) {
            return;
        }

        // Get history of current user
        $this->loggedInHistory = Upvote::$plugin->upvote_query->userHistory($currentUser->id);
    }

    /**
     * Set history of anonymous user.
     */
    public function getAnonymousHistory(): void
    {
        // Get request
        $request = Craft::$app->getRequest();

        // If running via command line, bail
        if ($request->getIsConsoleRequest()) {
            return;
        }

        // If login is required, bail
        if (Upvote::$plugin->getSettings()->requireLogin) {
            return;
        }

        // Get cookies object
        $cookies = $request->getCookies();

        // If cookie exists
        if ($cookies->has($this->userCookie)) {
            // Get history from cookie
            $cookieValue = $cookies->getValue($this->userCookie);
            $this->anonymousHistory = Json::decode($cookieValue);
        } else {
            // Initialize history and set cookie
            $this->anonymousHistory = [];
            Upvote::$plugin->upvote_vote->saveUserHistoryCookie();
        }

    }

    // ========================================================================= //

    /**
     * Compile an array of element's vote data.
     *
     * @param null|string $itemKey
     * @param null|int $userVote
     * @param bool $isAntivote
     * @return null|array
     */
    public function compileElementData(?string $itemKey, ?int $userVote = null, bool $isAntivote = false): ?array
    {
        // Get current user
        $currentUser = Craft::$app->user->getIdentity();

        // Split ID into array
        $parts = explode(':', $itemKey);

        // Get the element ID
        $elementId = (int) array_shift($parts);

        // If no element ID, bail
        if (!$elementId) {
            return null;
        }

        // Reassemble the remaining parts (in case the key contains a colon)
        $key = implode(':', $parts);

        // If no key, set to null
        if (!$key) {
            $key = null;
        }

        // Get user's vote history for this item
        $itemHistory = ($this->history[$itemKey] ?? null);

        // Set vote configuration
        $vote = [
            'id' => $elementId,
            'key' => $key,
            'itemKey' => $itemKey,
            'userId' => ($currentUser ? (int) $currentUser->id : null),
            'userVote' => ($userVote ?? $itemHistory),
            'isAntivote' => $isAntivote,
        ];

        /** @var Query $query */
        $query = Upvote::$plugin->upvote_query;

        // Get element totals from BEFORE the vote is calculated
        $totals = [
            'tally' => $query->tally($elementId, $key),
            'totalVotes' => $query->totalVotes($elementId, $key),
            'totalUpvotes' => $query->totalUpvotes($elementId, $key),
            'totalDownvotes' => $query->totalDownvotes($elementId, $key),
        ];

        // If existing vote was removed
        if ($isAntivote && $itemHistory) {
            // Create antivote
            $userVote = $itemHistory * -1;
            // Set total type
            $totalType = (1 === $userVote ? 'totalDownvotes' : 'totalUpvotes');
        } else {
            // Set total type
            $totalType = (1 === $userVote ? 'totalUpvotes' : 'totalDownvotes');
        }

        // If a vote was cast or removed
        if ($userVote) {

            // Add to tally
            $totals['tally'] += $userVote;

            // If removing vote
            if ($isAntivote) {
                // One less vote
                $totals['totalVotes']--;
                $totals[$totalType]--;
            } else {
                // One more vote
                $totals['totalVotes']++;
                $totals[$totalType]++;
            }

        }

        // Return element's vote data
        return array_merge($vote, $totals);
    }

    // ========================================================================= //

    /**
     * $userId could be a user ID or User object,
     * this ensures the $userId is an integer.
     *
     * @param null|User|int $userId
     */
    public function validateUserId(null|User|int &$userId): void
    {
        // No user by default
        $user = null;

        // Handle user ID
        if (!$userId) {
            // Default to logged in user
            $user = Craft::$app->user->getIdentity();
        } else {
            if (is_numeric($userId)) {
                // Get valid UserModel
                $user = Craft::$app->users->getUserById($userId);
            } else if (is_object($userId) && is_a($userId, User::class)) {
                // It's already a User model
                $user = $userId;
            }
        }

        // Get user ID, or rate anonymously
        $userId = ($user ? $user->id : null);
    }

    // ========================================================================= //

    /**
     * Generate combined item key.
     *
     * @param int $elementId
     * @param null|string $key
     * @param string $separator
     * @return string
     */
    public function setItemKey(int $elementId, ?string $key, string $separator = ':'): string
    {
        return $elementId.($key ? $separator.$key : '');
    }

    /**
     * Whether a key is valid.
     *
     * @param null|string $key
     * @return bool
     */
    public function validKey(?string $key): bool
    {
        return (null === $key || is_string($key) || is_numeric($key));
    }

    // ========================================================================= //

    /**
     * Whether the plugin contains legacy data.
     *
     * @return bool
     */
    public function hasLegacyData(): bool
    {
        return (new CraftQuery())
            ->select('[[totals.id]]')
            ->from('{{%upvote_elementtotals}} totals')
            ->where('[[totals.legacyTotal]] <> 0')
            ->exists();
    }

}
