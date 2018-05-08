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

use yii\base\Event;
use yii\web\Cookie;

use Craft;
use craft\base\Component;

use doublesecretagency\upvote\Upvote;
use doublesecretagency\upvote\events\VoteEvent;
use doublesecretagency\upvote\events\UnvoteEvent;
use doublesecretagency\upvote\records\ElementTotal;
use doublesecretagency\upvote\records\VoteLog;
use doublesecretagency\upvote\records\UserHistory;

/**
 * Class Vote
 * @since 2.0.0
 */
class Vote extends Component
{

    public $upvoteIcon;
    public $downvoteIcon;

    public $alreadyVoted = 'You have already voted on this element.';

    //
    public function init()
    {
        $this->_loadIcons();
    }

    //
    private function _loadIcons()
    {
        $this->upvoteIcon   = $this->_fa('caret-up');
        $this->downvoteIcon = $this->_fa('caret-down');
    }

    //
    private function _fa($iconType)
    {
        return '<i class="fa fa-'.$iconType.' fa-2x"></i>';
    }

    //
    public function setIcons($iconMap = [])
    {
        foreach ($iconMap as $type => $html) {
            switch ($type) {
                case 'up'   : $this->upvoteIcon   = $html; break;
                case 'down' : $this->downvoteIcon = $html; break;
            }
        }
    }

    // ========================================================================= //

    //
    public function castVote($elementId, $key, $vote)
    {
        // Get settings
        $settings = Upvote::$plugin->getSettings();

        // Prep return data
        $returnData = [
            'id'   => $elementId,
            'key'  => $key,
            'vote' => $vote,
        ];

        // Trigger event before a vote is cast
        if (Event::hasHandlers(Upvote::class, Upvote::EVENT_BEFORE_VOTE)) {
            Event::trigger(Upvote::class, Upvote::EVENT_BEFORE_VOTE, new VoteEvent($returnData));
        }

        // If login is required
        if ($settings->requireLogin) {
            // Update user history
            if (!$this->_updateUserHistoryDatabase($elementId, $key, $vote)) {
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
        $this->_updateVoteLog($elementId, $key, $vote);

        // Trigger event after a vote is cast
        if (Event::hasHandlers(Upvote::class, Upvote::EVENT_AFTER_VOTE)) {
            Event::trigger(Upvote::class, Upvote::EVENT_AFTER_VOTE, new VoteEvent($returnData));
        }

        return $returnData;

    }

    //
    public function removeVote($elementId, $key)
    {
        // Prep return data
        $returnData = [
            'id'       => $elementId,
            'key'      => $key,
            'antivote' => null,
        ];

        // Trigger event before a vote is removed
        if (Event::hasHandlers(Upvote::class, Upvote::EVENT_BEFORE_UNVOTE)) {
            Event::trigger(Upvote::class, Upvote::EVENT_BEFORE_UNVOTE, new UnvoteEvent($returnData));
        }

        //
        // FLAW:
        // It's impossible to know the value of $originalVote before killing cookie/DB.
        // Therefore, $antivote can't be contained in the 'onBeforeUnvote' event.
        //

        $originalVote = false;

        $this->_removeVoteFromCookie($elementId, $key, $originalVote);
        $this->_removeVoteFromDb($elementId, $key, $originalVote);

        if (!$originalVote) {
            return 'Unable to remove vote.';
        }

        $antivote = (-1 * $originalVote);
        $returnData['antivote'] = $antivote;
        $this->_updateElementTotals($elementId, $key, $antivote, true);
        $this->_updateVoteLog($elementId, $key, $antivote, true);

        // Trigger event after a vote is removed
        if (Event::hasHandlers(Upvote::class, Upvote::EVENT_AFTER_UNVOTE)) {
            Event::trigger(Upvote::class, Upvote::EVENT_AFTER_UNVOTE, new UnvoteEvent($returnData));
        }

        return $returnData;

    }

    // ========================================================================= //

    //
    private function _updateUserHistoryDatabase($elementId, $key, $vote)
    {
        $currentUser = Craft::$app->user->getIdentity();

        // If user is not logged in, return false
        if (!$currentUser) {
            return false;
        }

        // Load existing element history
        $record = UserHistory::findOne([
            'id' => $currentUser->id,
        ]);

        // Get item key
        $item = Upvote::$plugin->upvote->setItemKey($elementId, $key);

        // If record already exists
        if ($record) {
            $history = json_decode($record->history, true);
        } else {
            // Create new record if necessary
            $record = new UserHistory;
            $record->id = $currentUser->id;
            $history = [];
        }

        // Register vote
        $history[$item] = $vote;
        $record->history = $history;

        // Save
        return $record->save();
    }

    //
    private function _updateUserHistoryCookie($elementId, $key, $vote)
    {
        // Get anonymous history
        $history =& Upvote::$plugin->upvote->anonymousHistory;
        $item = Upvote::$plugin->upvote->setItemKey($elementId, $key);
        // Cast vote
        $history[$item] = $vote;
        $this->saveUserHistoryCookie();
        return true;
    }

    //
    public function saveUserHistoryCookie()
    {
        // Get cookie settings
        $cookieName = Upvote::$plugin->upvote->userCookie;
        $history    = Upvote::$plugin->upvote->anonymousHistory;
        $lifespan   = Upvote::$plugin->upvote->userCookieLifespan;
        // Set cookie
        $cookie = new Cookie();
        $cookie->name = $cookieName;
        $cookie->value = json_encode($history);
        $cookie->expire = time() + $lifespan;
        Craft::$app->getResponse()->getCookies()->add($cookie);
    }

    //
    private function _updateElementTotals($elementId, $key, $vote, $antivote = false)
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

    //
    private function _updateVoteLog($elementId, $key, $vote, $unvote = false)
    {
        // If not keeping a vote log, bail
        if (!Upvote::$plugin->getSettings()->keepVoteLog) {
            return false;
        }

        // Log vote
        $currentUser = Craft::$app->user->getIdentity();
        $record = new VoteLog;
        $record->elementId = $elementId;
        $record->voteKey   = $key;
        $record->userId    = ($currentUser ? $currentUser->id : null);
        $record->ipAddress = $_SERVER['REMOTE_ADDR'];
        $record->voteValue = $vote;
        $record->wasUnvote = (int) $unvote;
        $record->save();
    }

    //
    private function _removeVoteFromCookie($elementId, $key, &$originalVote)
    {
        // Get user history
        $history =& Upvote::$plugin->upvote->anonymousHistory;

        // If no user history, bail
        if (!$history) {
            return false;
        }

        // Get item key
        $item = Upvote::$plugin->upvote->setItemKey($elementId, $key);

        // If item doesn't exist in history, bail
        if (!array_key_exists($item, $history)) {
            return false;
        }

        // Get original vote value
        $originalVote = $history[$item];

        // Remove item from history
        unset($history[$item]);
        $this->saveUserHistoryCookie();
    }

    //
    private function _removeVoteFromDb($elementId, $key, &$originalVote)
    {
        $currentUser = Craft::$app->user->getIdentity();

        // If no current user, bail
        if (!$currentUser) {
            return false;
        }

        // Get user history
        $record = UserHistory::findOne([
            'id' => $currentUser->id,
        ]);

        // If no user history, bail
        if (!$record) {
            return false;
        }

        // Remove from database history
        $historyDb = json_decode($record->history, true);
        $item = Upvote::$plugin->upvote->setItemKey($elementId, $key);

        // If item doesn't exist in history, bail
        if (!array_key_exists($item, $historyDb)) {
            return false;
        }

        // Update original vote (by reference)
        if (!$originalVote) {
            $originalVote = $historyDb[$item];
        }

        // Remove item from history
        unset($historyDb[$item]);
        $record->history = $historyDb;
        $record->save();
    }

}