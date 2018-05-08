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
use craft\db\Query;
use craft\helpers\MigrationHelper;

/**
 * Migration: Add key column
 * @since 2.0.0
 */
class m151217_000000_upvote_addKeyColumn extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_addVoteKeyColumn('{{%upvote_elementtallies}}', 'id');
        $this->_addVoteKeyColumn('{{%upvote_votelog}}', 'elementId');
        $this->_addElementIdColumn();
        $this->_copyForeignKeyData();
        $this->_removeZeros();
        $this->_cleanupColumns();
        $this->_renumberPrimaryKey();
    }

    private function _addVoteKeyColumn($table, $after)
    {
        if (!$this->db->columnExists($table, 'voteKey')) {
            $this->addColumn($table, 'voteKey', $this->string()->after($after));
        }
    }

    private function _addElementIdColumn()
    {
        if (!$this->db->columnExists('{{%upvote_elementtallies}}', 'elementId')) {
            $this->addColumn('{{%upvote_elementtallies}}', 'elementId', $this->integer()->after('id'));
        }
        $this->addForeignKey(null, '{{%upvote_elementtallies}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE');
    }

    private function _copyForeignKeyData()
    {
        // Get data
        $oldData = (new Query())
            ->select(['id'])
            ->from(['{{%upvote_elementtallies}}'])
            ->orderBy('id')
            ->all($this->db);
        // Copy data
        foreach ($oldData as $row) {
            $newData = ['elementId' => $row['id']];
            $this->update('{{%upvote_elementtallies}}', $newData, ['id' => $row['id']]);
        }
        // After values have been transferred, disallow null elementId values
        $this->alterColumn('{{%upvote_elementtallies}}', 'elementId', $this->integer()->notNull());
    }

    private function _removeZeros()
    {
        $this->delete('{{%upvote_elementtallies}}', 'tally=0');
    }

    private function _cleanupColumns()
    {
        MigrationHelper::dropForeignKeyIfExists('{{%upvote_elementtallies}}', ['id'], $this);
        $this->alterColumn('{{%upvote_elementtallies}}', 'id', $this->integer().' NOT NULL AUTO_INCREMENT');
    }

    private function _renumberPrimaryKey()
    {
        // Get data
        $oldData = (new Query())
            ->select(['elementId'])
            ->from(['{{%upvote_elementtallies}}'])
            ->orderBy('id')
            ->all($this->db);
        // Renumber rows
        $i = 1;
        foreach ($oldData as $row) {
            $newData = ['id' => $i++];
            $this->update('{{%upvote_elementtallies}}', $newData, ['elementId' => $row['elementId']]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m151217_000000_upvote_addKeyColumn cannot be reverted.\n";

        return false;
    }

}