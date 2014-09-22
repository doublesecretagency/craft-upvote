<?php
namespace Craft;

class Upvote_UserHistoryRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'upvote_userhistories';
	}

	protected function defineAttributes()
	{
		return array(
			'history' => AttributeType::Mixed,
		);
	}

	public function defineRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'UserRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

}