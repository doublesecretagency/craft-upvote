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

    // Generate combined item key
    public function setItemKey($elementId, $key)
    {
        return $elementId.($key ? ':'.$key : '');
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
            $this->anonymousHistory = json_decode($cookieValue, true);
        }

        // If no anonymous history
        if (!$this->anonymousHistory) {
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
            } else if (is_object($userId) && is_a($userId, 'craft\\elements\\User')) {
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