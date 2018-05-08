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

namespace doublesecretagency\upvote\migrations;

use craft\db\Migration;

/**
 * Migration: Split upvote & downvote columns
 * @since 2.0.0
 */
class m171024_000000_upvote_splitUpvoteDownvoteColumns extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_addVoteTotalColumn('upvoteTotal', 'voteKey');
        $this->_addVoteTotalColumn('downvoteTotal', 'upvoteTotal');
        $this->_renameColumn();
        $this->_tweakLegacy();
        $this->_renameTable();
    }

    // Add `upvoteTotal` & `downvoteTotal` columns
    private function _addVoteTotalColumn($column, $after)
    {
        if (!$this->db->columnExists('{{%upvote_elementtallies}}', $column)) {
            $this->addColumn('{{%upvote_elementtallies}}', $column, $this->integer()->defaultValue(0)->after($after));
        }
    }

    // Rename `tally` to `legacyTotal`
    private function _renameColumn()
    {
        $this->renameColumn('{{%upvote_elementtallies}}', 'tally', 'legacyTotal');
    }

    // Make adjustments to `legacyTotal` column
    private function _tweakLegacy()
    {
        $this->alterColumn('{{%upvote_elementtallies}}', 'legacyTotal', $this->integer()->defaultValue(0));
    }

    // Rename `upvote_elementtallies` to `upvote_elementtotals`
    private function _renameTable()
    {
        $this->renameTable('{{%upvote_elementtallies}}', '{{%upvote_elementtotals}}');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171024_000000_upvote_splitUpvoteDownvoteColumns cannot be reverted.\n";

        return false;
    }

}