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

use craft\base\Component;
use craft\db\Query as CraftQuery;
use craft\elements\db\ElementQuery;
use craft\helpers\Json;
use doublesecretagency\upvote\Upvote;
use doublesecretagency\upvote\records\ElementTotal;
use doublesecretagency\upvote\records\UserHistory;
use yii\db\Expression;

/**
 * Class Query
 * @since 2.0.0
 */
class Query extends Component
{

    /**
     * Get the cumulative vote value of a specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @return int
     */
    public function tally(int $elementId, ?string $key = null): int
    {
        // Get matching vote totals record
        $record = ElementTotal::findOne([
            'elementId' => $elementId,
            'voteKey'   => ($key ?: null),
        ]);

        // If no record, return zero
        if (!$record) {
            return 0;
        }

        // Calculate and return total
        $subtotal = ($record->upvoteTotal - $record->downvoteTotal);
        return ($subtotal + $record->legacyTotal);
    }

    // ========================================================================= //

    /**
     * Get the total number of votes for a specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @return int
     */
    public function totalVotes(int $elementId, ?string $key = null): int
    {
        $record = $this->_getRecord($elementId, $key);
        return ($record ? ($record->upvoteTotal + $record->downvoteTotal) : 0);
    }

    /**
     * Get the total number of upvotes for a specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @return int
     */
    public function totalUpvotes(int $elementId, ?string $key = null): int
    {
        $record = $this->_getRecord($elementId, $key);
        return ($record->upvoteTotal ?? 0);
    }

    /**
     * Get the total number of downvotes for a specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @return int
     */
    public function totalDownvotes(int $elementId, ?string $key = null): int
    {
        $record = $this->_getRecord($elementId, $key);
        return ($record->downvoteTotal ?? 0);
    }

    /**
     * Get the totals record of a specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @return null|ElementTotal
     */
    private function _getRecord(int $elementId, ?string $key): ?ElementTotal
    {
        return ElementTotal::findOne([
            'elementId' => $elementId,
            'voteKey'   => $key,
        ]);
    }

    // ========================================================================= //

    /**
     * Get the vote history of a specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @return array
     */
    public function elementHistory(int $elementId, ?string $key = null): array
    {
        // Get the complete collection of voting records
        $allRecords = UserHistory::find()->all();

        // If no voting records exist, return an empty array
        if (!$allRecords) {
            return [];
        }

        // Set value to compare against
        $match = ($key ? "$elementId:$key" : $elementId);

        // Initialize element history
        $elementHistory = [];

        // Loop through all user history records
        foreach ($allRecords as $record) {

            // Get the user vote history of each record
            $userHistory = Json::decode($record->history);

            // If matching vote exists in history
            if (isset($userHistory[$match])) {
                // Include the user ID and their respective vote
                $elementHistory[$record->id] = $userHistory[$match];
            }

        }

        // Return complete element history as an array
        return $elementHistory;
    }

    // ========================================================================= //

    /**
     * Get the vote history of a specified user.
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
     * Get the vote history of a specified user, organized by unique keys.
     * Optionally filter to a subset of votes, based on a specified key.
     *
     * @param int $userId
     * @param null|string $key
     * @return array
     */
    public function userHistoryByKey(int $userId, ?string $key = null): array
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
                list($elementId, $k) = explode(':', $item);
            } else {
                // Get element ID with no key
                list($elementId, $k) = [$item, null];
            }

            // Recompile history
            $historyWithKeys[(string) $k][(int) $elementId] = $vote;

        }

        // If not looking for a subset, return the whole thing
        if (null === $key) {
            return $historyWithKeys;
        }

        // Return a filtered subset of the history
        return $historyWithKeys[$key] ?? [];
    }

    /**
     * Retrieve the specific vote cast by a specific user.
     * If the user has not voted on that particular element,
     * the value will be returned as zero (0).
     *
     * @param int $userId
     * @param int $elementId
     * @param null|string $key
     * @return int
     */
    public function userVote(int $userId, int $elementId, ?string $key = null): int
    {
        // Get user's history in regard to specified key
        $keyHistory = $this->userHistoryByKey($userId, $key);

        // Return specific vote, or zero (0) if no vote exists
        return ($keyHistory[$elementId] ?? 0);
    }

    // ========================================================================= //

    /**
     * Sort the query by the highest voted elements.
     *
     * @param ElementQuery $query
     * @param null|string $key
     */
    public function orderByTally(ElementQuery $query, ?string $key = null): void
    {
        // Get and sort element IDs
        $elementIds = $this->_elementIdsByTally($key);

        // If no element IDs, bail
        if (!$elementIds) {
            return;
        }

        // Match order to elementIds
        $ids = implode(', ', $elementIds);
        $query->orderBy = [new Expression("field([[elements.id]], {$ids}) desc")];
    }

    /**
     * Get and sort element IDs.
     *
     * @param null|string $key
     * @return array
     */
    private function _elementIdsByTally(?string $key): array
    {
        // If key isn't valid, bail with empty array
        if (!Upvote::$plugin->upvote->validKey($key)) {
            return [];
        }

        // Adjust conditions based on whether a key was provided
        if (null === $key) {
            $conditions = '[[totals.voteKey]] is null';
        } else {
            $conditions = ['[[totals.voteKey]]' => $key];
        }

        // Compile dynamic `tally` field
        $upvotes   = 'coalesce([[totals.upvoteTotal]], 0)';
        $downvotes = 'coalesce([[totals.downvoteTotal]], 0)';
        $legacy    = 'coalesce([[totals.legacyTotal]], 0)';
        $tally = "({$upvotes} - {$downvotes} + {$legacy})";

        // Order for subquery
        $subqueryOrder = "[[tally]] desc, [[totals.elementId]] desc";

        // Get all matching items from `totals` table
        $subquery = (new CraftQuery())
            ->select([
                '[[totals.elementId]]',
                "{$tally} as [[tally]]"
            ])
            ->from('{{%upvote_elementtotals}} totals')
            ->where($conditions)
            ->orderBy([new Expression($subqueryOrder)]);

        // Order for main query
        $queryOrder = 'coalesce([[subquery.tally]], 0), [[elements.id]] desc';

        // Join with elements table to sort by tally
        $elementIds = (new CraftQuery())
            ->select('[[elements.id]]')
            ->from('{{%elements}} elements')
            ->leftJoin(['subquery' => $subquery], '[[elements.id]] = [[subquery.elementId]]')
            ->orderBy([new Expression($queryOrder)])
            ->column();

        // Return element IDs in order of highest voted
        return $elementIds;
    }

}
