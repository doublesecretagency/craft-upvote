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
use craft\helpers\Json;
use doublesecretagency\upvote\Upvote;
use doublesecretagency\upvote\events\VoteEvent;
use doublesecretagency\upvote\models\Settings;
use doublesecretagency\upvote\records\ElementTotal;
use doublesecretagency\upvote\records\UserHistory;
use doublesecretagency\upvote\records\VoteLog;
use yii\base\Event;
use yii\web\Cookie;

/**
 * Class Vote
 * @since 2.0.0
 */
class Vote extends Component
{

    /**
     * @var string Icon for up vote.
     */
    public string $upvoteIcon = 'upvote';

    /**
     * @var string Icon for down vote.
     */
    public string $downvoteIcon = 'downvote';

    /**
     * @var string Message that user has already voted.
     */
    public string $alreadyVoted = 'You have already voted on this element.';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        // Load icons
        $this->setIcons([
            'up'   => $this->_fa('caret-up'),
            'down' => $this->_fa('caret-down')
        ]);
    }

    /**
     * Set new icons to use.
     *
     * @param array $iconMap
     */
    public function setIcons(array $iconMap = []): void
    {
        foreach ($iconMap as $type => $html) {
            switch ($type) {
                case 'up'   : $this->upvoteIcon   = $html; break;
                case 'down' : $this->downvoteIcon = $html; break;
            }
        }
    }

    /**
     * Generate complete Font Awesome icon.
     *
     * @param string $iconType
     * @return string
     */
    private function _fa(string $iconType): string
    {
        return '<i class="fa fa-'.$iconType.' fa-2x"></i>';
    }

    // ========================================================================= //

    /**
     * Cast a vote on the specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @param int $vote
     * @param null|int $userId
     * @return null|array|string
     */
    public function castVote(int $elementId, ?string $key, int $vote, ?int $userId = null): null|array|string
    {
        /** @var Settings $settings */
        $settings = Upvote::$plugin->getSettings();

        /** @var UpvoteService $upvote */
        $upvote = Upvote::$plugin->upvote;

        // Ensure the user ID is valid
        $upvote->validateUserId($userId);

        // Prep return data
        $itemKey = $upvote->setItemKey($elementId, $key);
        $returnData = $upvote->compileElementData($itemKey, $vote);

        // DEPRECATED: REMOVE IN NEXT MAJOR VERSION
        $returnData['vote'] = $vote;

        // Update original history
        $upvote->history[$itemKey] = $vote;

        // Trigger event before a vote is cast
        if (Event::hasHandlers(Upvote::class, Upvote::EVENT_BEFORE_VOTE)) {
            Event::trigger(Upvote::class, Upvote::EVENT_BEFORE_VOTE, new VoteEvent($returnData));
        }

        // If login is required
        if ($settings->requireLogin) {
            // Update user history
            if (!$this->_updateUserHistoryDatabase($elementId, $key, $vote, $userId)) {
                return $this->alreadyVoted;
            }
        } else {
            // Update user cookie
            if (!$this->_updateUserHistoryCookie($elementId, $key, $vote)) {
                return $this->alreadyVoted;
            }
        }

        // Update element tally
        $this->_updateElementTotals($elementId, $key, $vote);
        $this->_updateVoteLog($elementId, $key, $vote, $userId);

        // Trigger event after a vote is cast
        if (Event::hasHandlers(Upvote::class, Upvote::EVENT_AFTER_VOTE)) {
            Event::trigger(Upvote::class, Upvote::EVENT_AFTER_VOTE, new VoteEvent($returnData));
        }

        // Return data
        return $returnData;
    }

    /**
     * Remove a vote on the specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @param null|int $userId
     * @return null|array|string
     */
    public function removeVote(int $elementId, ?string $key, ?int $userId = null): null|array|string
    {
        /** @var UpvoteService $upvote */
        $upvote = Upvote::$plugin->upvote;

        // Ensure the user ID is valid
        $upvote->validateUserId($userId);

        // Prep return data
        $itemKey = $upvote->setItemKey($elementId, $key);
        $returnData = $upvote->compileElementData($itemKey, null, true);

        // Get original vote
        $originalVote = $returnData['userVote'];

        // If no original vote, bail
        if (!$originalVote) {
            return 'Unable to remove vote. No vote was ever cast.';
        }

        // Get antivote
        $antivote = (-1 * $originalVote);
        $returnData['userVote'] = $antivote;
        $returnData['antivote'] = $antivote; // DEPRECATED: REMOVE IN NEXT MAJOR VERSION

        // Trigger event before a vote is removed
        if (Event::hasHandlers(Upvote::class, Upvote::EVENT_BEFORE_UNVOTE)) {
            Event::trigger(Upvote::class, Upvote::EVENT_BEFORE_UNVOTE, new VoteEvent($returnData));
        }

        // Remove user vote
        $this->_removeVoteFromCookie($elementId, $key);
        $this->_removeVoteFromDb($elementId, $key, $userId);

        // Update vote logs
        $this->_updateElementTotals($elementId, $key, $antivote, true);
        $this->_updateVoteLog($elementId, $key, $antivote, $userId, true);

        // Remove vote from user history
        unset($upvote->history[$itemKey]);

        // Trigger event after a vote is removed
        if (Event::hasHandlers(Upvote::class, Upvote::EVENT_AFTER_UNVOTE)) {
            Event::trigger(Upvote::class, Upvote::EVENT_AFTER_UNVOTE, new VoteEvent($returnData));
        }

        // Return data
        return $returnData;
    }

    // ========================================================================= //

    /**
     * Update the user's vote history in the database.
     *
     * @param int $elementId
     * @param null|string $key
     * @param int $vote
     * @param int $userId
     * @return bool
     */
    private function _updateUserHistoryDatabase(int $elementId, ?string $key, int $vote, int $userId): bool
    {
        // If user is not logged in, return false
        if (!$userId) {
            return false;
        }
        // Load existing element history
        $record = UserHistory::findOne([
            'id' => $userId,
        ]);

        // Get item key
        $item = Upvote::$plugin->upvote->setItemKey($elementId, $key);

        // If record already exists
        if ($record) {
            $history = Json::decode($record->history);
            // If user has already voted on element, bail
            if (isset($history[$item])) {
                return false;
            }
        } else {
            // Create new record if necessary
            $record = new UserHistory;
            $record->id = $userId;
            $history = [];
        }

        // Register vote
        $history[$item] = $vote;
        $record->history = $history;

        // Save
        return $record->save();
    }

    /**
     * Update the user's vote history cookie.
     *
     * @param int $elementId
     * @param null|string $key
     * @param int $vote
     * @return bool
     */
    private function _updateUserHistoryCookie(int $elementId, ?string $key, int $vote): bool
    {
        /** @var UpvoteService $upvote */
        $upvote = Upvote::$plugin->upvote;

        // Compile the item key
        $item = $upvote->setItemKey($elementId, $key);

        // Cast the anonymous vote
        $upvote->anonymousHistory[$item] = $vote;

        // Save the cookie
        $this->saveUserHistoryCookie();

        // Always return true
        return true;
    }

    /**
     * Save the current anonymous history to a cookie.
     */
    public function saveUserHistoryCookie(): void
    {
        /** @var UpvoteService $upvote */
        $upvote = Upvote::$plugin->upvote;

        // Get cookie settings
        $cookieName = $upvote->userCookie;
        $history    = $upvote->anonymousHistory;
        $lifespan   = $upvote->userCookieLifespan;

        // Set cookie
        $cookie = new Cookie();
        $cookie->name = $cookieName;
        $cookie->value = Json::encode($history);
        $cookie->expire = time() + $lifespan;
        Craft::$app->getResponse()->getCookies()->add($cookie);
    }

    /**
     * Update the Element Total in the database.
     *
     * @param int $elementId
     * @param null|string $key
     * @param int $vote
     * @param bool $antivote
     * @return bool
     */
    private function _updateElementTotals(int $elementId, ?string $key, int $vote, bool $antivote = false): bool
    {
        // Load existing element totals
        $record = ElementTotal::findOne([
            'elementId' => $elementId,
            'voteKey'   => $key,
        ]);

        // If no totals record exists, create new
        if (!$record) {
            $record = new ElementTotal;
            $record->elementId     = $elementId;
            $record->voteKey       = $key;
            $record->upvoteTotal   = 0;
            $record->downvoteTotal = 0;
        }

        // If vote is being removed
        if ($antivote) {
            // Vote direction
            $antiUpvote   = (-1 == $vote);
            $antiDownvote = ( 1 == $vote);
            // Whether to remove a legacy vote
            $removeLegacyUpvote   = ($antiUpvote   && $record->legacyTotal > 0);
            $removeLegacyDownvote = ($antiDownvote && $record->legacyTotal < 0);
            // If removing legacy vote
            if ($removeLegacyUpvote) {
                $record->legacyTotal--;
            } else if ($removeLegacyDownvote) {
                $record->legacyTotal++;
            } else {
                // Register unvote (default behavior)
                switch ($vote) {
                    case  1:
                        $record->downvoteTotal--;
                        break;
                    case -1:
                        $record->upvoteTotal--;
                        break;
                }
            }
        } else {
            // Register vote
            switch ($vote) {
                case  1:
                    $record->upvoteTotal++;
                    break;
                case -1:
                    $record->downvoteTotal++;
                    break;
            }
        }

        // Save
        return $record->save();
    }

    /**
     * Update the Vote Log in the database.
     *
     * @param int $elementId
     * @param null|string $key
     * @param int $vote
     * @param int $userId
     * @param bool $unvote
     * @return bool
     */
    private function _updateVoteLog(int $elementId, ?string $key, int $vote, int $userId, bool $unvote = false): bool
    {
        // If not keeping a vote log, bail
        if (!Upvote::$plugin->getSettings()->keepVoteLog) {
            return true;
        }

        // Log vote
        $record = new VoteLog;
        $record->elementId = $elementId;
        $record->voteKey   = $key;
        $record->userId    = $userId;
        $record->ipAddress = $_SERVER['REMOTE_ADDR'];
        $record->voteValue = $vote;
        $record->wasUnvote = (int) $unvote;
        return $record->save();
    }

    // ========================================================================= //

    /**
     * Remove a vote from the anonymous history cookie.
     *
     * @param int $elementId
     * @param null|string $key
     */
    private function _removeVoteFromCookie(int $elementId, ?string $key): void
    {
        /** @var UpvoteService $upvote */
        $upvote = Upvote::$plugin->upvote;

        // If no anonymous history exists, bail
        if (!$upvote->anonymousHistory) {
            return;
        }

        // Get item key
        $item = $upvote->setItemKey($elementId, $key);

        // If item doesn't exist in anonymous history, bail
        if (!isset($upvote->anonymousHistory[$item])) {
            return;
        }

        // Remove item from anonymous history
        unset($upvote->anonymousHistory[$item]);
        $this->saveUserHistoryCookie();
    }

    /**
     * Remove a vote from the User History in the database.
     *
     * @param int $elementId
     * @param null|string $key
     * @param null|int $userId
     * @return bool
     */
    private function _removeVoteFromDb(int $elementId, ?string $key, ?int $userId): bool
    {
        // If no user ID, bail
        if (!$userId) {
            return false;
        }

        // Get user history
        $record = UserHistory::findOne([
            'id' => $userId,
        ]);

        // If no user history, bail
        if (!$record) {
            return false;
        }

        // Remove from database history
        $historyDb = Json::decode($record->history);
        $item = Upvote::$plugin->upvote->setItemKey($elementId, $key);

        // If item doesn't exist in history, bail
        if (!isset($historyDb[$item])) {
            return false;
        }

        // Remove item from history
        unset($historyDb[$item]);
        $record->history = $historyDb;
        return $record->save();
    }

}
