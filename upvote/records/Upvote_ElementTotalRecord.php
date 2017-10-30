<?php
namespace Craft;

class Upvote_ElementTotalRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'upvote_elementtotals';
	}

	protected function defineAttributes()
	{
		// Vote total columns
		$voteColumn = array(
			AttributeType::Number,
			'column'  => ColumnType::Int,
			'length'  => 11,
			'default' => 0,
		);
		return array(
			'voteKey'       => AttributeType::String,
			'upvoteTotal'   => $voteColumn,
			'downvoteTotal' => $voteColumn,
			'legacyTotal'   => $voteColumn,
		);
	}

	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

}