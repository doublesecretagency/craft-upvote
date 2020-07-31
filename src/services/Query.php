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

use craft\helpers\Json;
use yii\db\Expression;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQuery;

use doublesecretagency\upvote\Upvote;
use doublesecretagency\upvote\records\ElementTotal;
use doublesecretagency\upvote\records\UserHistory;

/**
 * Class Query
 * @since 2.0.0
 */
class Query extends Component
{

    //
    public function tally($elementId, $key = null): int
    {
        // If key is falsey, force NULL
        if (!$key) {
            $key = null;
        }
        // Get matching vote totals record
        $record = ElementTotal::findOne([
            'elementId' => $elementId,
            'voteKey'   => $key,
        ]);
        // If no record, return zero
        if (!$record) {
            return 0;
        }
        // Calculate and return total
        $subtotal = ($record->upvoteTotal - $record->downvoteTotal);
        return ($subtotal + $record->legacyTotal);
    }

    // ========================================================================

    //
    public function totalVotes($elementId, $key = null): int
    {
        $record = $this->_getRecord($elementId, $key);
        return ($record ? ($record->upvoteTotal + $record->downvoteTotal) : 0);
    }

    //
    public function totalUpvotes($elementId, $key = null): int
    {
        $record = $this->_getRecord($elementId, $key);
        return ($record ? $record->upvoteTotal : 0);
    }

    //
    public function totalDownvotes($elementId, $key = null): int
    {
        $record = $this->_getRecord($elementId, $key);
        return ($record ? $record->downvoteTotal : 0);
    }

    // Get matching totals record
    private function _getRecord($elementId, $key)
    {
        return ElementTotal::findOne([
            'elementId' => $elementId,
            'voteKey'   => $key,
        ]);
    }

    // ========================================================================

    /**
     * Get the history of a specified user.
     *
     * @param int $userId
     * @return array
     */
    public function userHistory(int $userId): array
    {
        // Get the complete history of the specified user
        $record = UserHistory::findOne([
            'id' => $userId,
        ]);

        // If no user history exists, return an empty array
        if (!$record) {
            return [];
        }

        // Return complete user history as an array
        return Json::decode($record->history);
    }

    /**
     * Get the history of a specified user, organized by unique keys.
     * Optionally filter to a subset of votes, based on a specified key.
     *
     * @param int $userId
     * @param string|false $keyFilter
     * @return array
     */
    public function userHistoryByKey(int $userId, $keyFilter = false): array
    {
        // Get complete user history
        $history = $this->userHistory($userId);

        // If there's no history, return an empty array
        if (!$history) {
            return [];
        }

        // Initialize history with keys
        $historyWithKeys = [];

        // Loop through entire history
        foreach ($history as $item => $vote) {

            // Whether the vote include a unique key
            $hasKey = (false !== strpos($item, ':'));

            // If the vote is using a unique key
            if ($hasKey) {
                // Split element ID and key
                list($elementId, $key) = explode(':', $item);
            } else {
                // Get element ID with no key
                list($elementId, $key) = [$item, null];
            }

            // Recompile history
            $historyWithKeys[(string) $key][(int) $elementId] = $vote;

        }

        // If not looking for a subset, return the whole thing
        if (false === $keyFilter) {
            return $historyWithKeys;
        }

        // Return a filtered subset of the history
        return $historyWithKeys[$keyFilter] ?? [];
    }

    /**
     * Retrieve the specific vote cast by a specific user.
     * If the user has not voted on that particular element,
     * the value will be returned as zero (0).
     *
     * @param int $userId
     * @param int $elementId
     * @param string|false $key
     * @return int
     */
    public function userVote(int $userId, int $elementId, $key = false): int
    {
        // Get user's history with regards to specified key
        $keyHistory = $this->userHistoryByKey($userId, $key);

        // Return specific vote, or zero (0) if no vote exists
        return ($keyHistory[$elementId] ?? 0);
    }

    // ========================================================================

    //
    public function orderByTally(ElementQuery $query, $key = null)
    {
        // Collect and sort elementIds
        $elementIds = $this->_elementIdsByTally($key);

        // If no element IDs, bail
        if (!$elementIds) {
            return false;
        }

        // Match order to elementIds
        $ids = implode(', ', $elementIds);
        $query->orderBy = [new Expression("field([[elements.id]], {$ids}) desc")];
    }

    //
    private function _elementIdsByTally($key)
    {
        // If key isn't valid, bail
        if (!Upvote::$plugin->upvote->validKey($key)) {
            return false;
        }

        // Adjust conditions based on whether a key was provided
        if (null === $key) {
            $conditions = '[[totals.voteKey]] is null';
        } else {
            $conditions = ['[[totals.voteKey]]' => $key];
        }

        // Construct order SQL
        $upvotes   = 'ifnull([[totals.upvoteTotal]], 0)';
        $downvotes = 'ifnull([[totals.downvoteTotal]], 0)';
        $legacy    = 'ifnull([[totals.legacyTotal]], 0)';
        $order     = "({$upvotes} - {$downvotes} + {$legacy}) desc, [[elements.id]] desc";

        // Join with elements table to sort by tally
        $elementIds = (new craft\db\Query())
            ->select('[[elements.id]]')
            ->from('{{%elements}} elements')
            ->where($conditions)
            ->leftJoin('{{%upvote_elementtotals}} totals', '[[elements.id]] = [[totals.elementId]]')
            ->orderBy([new Expression($order)])
            ->column();

        // Return elementIds
        return array_reverse($elementIds);
    }

}
