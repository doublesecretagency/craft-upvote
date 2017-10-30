<?php
namespace Craft;

class m171024_000000_upvote_splitUpvoteDownvoteColumns extends BaseMigration
{

	public function safeUp()
	{
		$table = 'upvote_elementtallies';
		$this->_addNewColumns($table);
		$this->_renameColumn($table);
		$this->_tweakLegacy($table);
		$this->_renameTable($table, 'upvote_elementtotals');
		return true;
	}

	// Add `upvoteTotal` & `downvoteTotal` columns
	private function _addNewColumns($table)
	{
		$colType = array(ColumnType::Int, 'default' => 0);
		$this->addColumnAfter($table, 'upvoteTotal',   $colType, 'voteKey');
		$this->addColumnAfter($table, 'downvoteTotal', $colType, 'upvoteTotal');
	}

	// Rename `tally` to `legacyTotal`
	private function _renameColumn($table)
	{
		$this->renameColumn($table, 'tally', 'legacyTotal');
	}

	// Make adjustments to `legacyTotal` column
	private function _tweakLegacy($table)
	{
		$voteColumn = array(
			'column'  => ColumnType::Int,
			'length'  => 11,
			'default' => 0,
		);
		$this->alterColumn($table, 'legacyTotal', $voteColumn);
	}

	// Rename `upvote_elementtallies` to `upvote_elementtotals`
	private function _renameTable($oldName, $newName)
	{
		$this->renameTable($oldName, $newName);
	}

}