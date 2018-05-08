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
 * Installation Migration
 * @since 2.0.0
 */
class Install extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%upvote_elementtotals}}');
        $this->dropTableIfExists('{{%upvote_votelog}}');
        $this->dropTableIfExists('{{%upvote_userhistories}}');
    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {
        $this->createTable('{{%upvote_elementtotals}}', [
            'id'            => $this->primaryKey(),
            'elementId'     => $this->integer()->notNull(),
            'voteKey'       => $this->string(),
            'upvoteTotal'   => $this->integer()->defaultValue(0),
            'downvoteTotal' => $this->integer()->defaultValue(0),
            'legacyTotal'   => $this->integer()->defaultValue(0),
            'dateCreated'   => $this->dateTime()->notNull(),
            'dateUpdated'   => $this->dateTime()->notNull(),
            'uid'           => $this->uid(),
        ]);
        $this->createTable('{{%upvote_votelog}}', [
            'id'          => $this->primaryKey(),
            'elementId'   => $this->integer()->notNull(),
            'voteKey'     => $this->string(),
            'userId'      => $this->integer(),
            'ipAddress'   => $this->string(),
            'voteValue'   => $this->tinyInteger(2),
            'wasUnvote'   => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'         => $this->uid(),
        ]);
        $this->createTable('{{%upvote_userhistories}}', [
            'id'          => $this->integer()->notNull(),
            'history'     => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'         => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%upvote_elementtotals}}', ['elementId']);
        $this->createIndex(null, '{{%upvote_votelog}}',       ['elementId']);
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%upvote_elementtotals}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%upvote_votelog}}',       ['elementId'], '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%upvote_userhistories}}', ['id'],        '{{%users}}',    ['id'], 'CASCADE');
    }

}