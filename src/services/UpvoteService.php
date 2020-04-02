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
use craft\elements\User;
use craft\helpers\Json;

use doublesecretagency\upvote\Upvote;

/**
 * Class UpvoteService
 * @since 2.0.0
 */
class UpvoteService extends Component
{

    public $settings;

    public $userCookie = 'VoteHistory';
    public $userCookieLifespan = 315569260; // Lasts 10 years
    public $anonymousHistory = [];
    public $loggedInHistory = [];
    public $history;

    //
    public function init()
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

    // Generate combined item key
    public function setItemKey($elementId, $key, $separator = ':')
    {
        return $elementId.($key ? $separator.$key : '');
    }

    // Get history of logged-in user
    public function getUserHistory()
    {
        // If table has not been created yet, bail
        if (!Craft::$app->getDb()->tableExists('{{%upvote_userhistories}}')) {
            return false;
        }

        // Get current user
        $currentUser = Craft::$app->user->getIdentity();

        // If no current user, bail
        if (!$currentUser) {
            return false;
        }

        // Get history of current user
        $this->loggedInHistory = Upvote::$plugin->upvote_query->userHistory($currentUser->id);
    }

    // Get history of anonymous user
    public function getAnonymousHistory()
    {
        // Get request
        $request = Craft::$app->getRequest();

        // If running via command line, bail
        if ($request->getIsConsoleRequest()) {
            return false;
        }

        // If login is required, bail
        if (Upvote::$plugin->getSettings()->requireLogin) {
            return false;
        }

        // Get cookies object
        $cookies = $request->getCookies();

        // If cookie exists
        if ($cookies->has($this->userCookie)) {
            // Get anonymous history
            $cookieValue = $cookies->getValue($this->userCookie);
            $this->anonymousHistory = Json::decode($cookieValue);
        }

        // If no anonymous history and cookie oes not already exists
        if (!$this->anonymousHistory && !$cookies->has($this->userCookie)) {
            // Initialize anonymous history
            $this->anonymousHistory = [];
            Upvote::$plugin->upvote_vote->saveUserHistoryCookie();
        }

    }

    // Check if a key is valid
    public function validKey($key)
    {
        return (null === $key || is_string($key) || is_numeric($key));
    }

    // ========================================================================= //

    /**
     */
    public function compileElementData($itemKey, $userVote = null, $isAntivote = false)
    {
        // Get current user
        $currentUser = Craft::$app->user->getIdentity();

        // Split ID into array
        $parts = explode(':', $itemKey);

        // Get the element ID
        $elementId = (int) array_shift($parts);

        // If no element ID, bail
        if (!$elementId) {
            return false;
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

        // Get element totals from BEFORE the vote is calculated
        $totals = [
            'tally' => Upvote::$plugin->upvote_query->tally($elementId, $key),
            'totalVotes' => Upvote::$plugin->upvote_query->totalVotes($elementId, $key),
            'totalUpvotes' => Upvote::$plugin->upvote_query->totalUpvotes($elementId, $key),
            'totalDownvotes' => Upvote::$plugin->upvote_query->totalDownvotes($elementId, $key),
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

    // $userId can be valid user ID or UserModel
    public function validateUserId(&$userId)
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

    // Does the plugin contain legacy data?
    public function hasLegacyData(): bool
    {
        return (new craft\db\Query())
            ->select('[[totals.id]]')
            ->from('{{%upvote_elementtotals}} totals')
            ->where('[[totals.legacyTotal]] <> 0')
            ->exists();
    }

}
