<?php
namespace Craft;

class Upvote_VoteLogRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'upvote_votelog';
	}

	protected function defineAttributes()
	{
		return array(
			'userId' => AttributeType::Number,
			'ipAddress' => AttributeType::String,
			'voteValue' => array(
				// tinyint(2)
				AttributeType::Number,
				'column' => ColumnType::TinyInt,
				'length' => 2,
			),
			'wasUnvote' => array(
				// tinyint(1)
				AttributeType::Number,
				'column' => ColumnType::TinyInt,
				'length' => 1,
			),
		);
	}

	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

}