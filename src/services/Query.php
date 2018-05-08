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

    //
    public function userHistory($userId = null): array
    {
        if (!$userId) {
            return [];
        }
        $record = UserHistory::findOne([
            'id' => $userId,
        ]);
        if (!$record) {
            return [];
        }
        return json_decode($record->history, true);
    }

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