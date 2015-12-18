<?php
namespace Craft;

class Upvote_ElementTallyRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'upvote_elementtallies';
	}

	protected function defineAttributes()
	{
		return array(
			'voteKey' => AttributeType::String,
			'tally' => AttributeType::Number,
		);
	}

	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

}