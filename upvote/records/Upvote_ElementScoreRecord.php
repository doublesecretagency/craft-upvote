<?php
namespace Craft;

class Upvote_ElementScoreRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'upvote_elementscores';
	}

	protected function defineAttributes()
	{
		return array(
			'score' => AttributeType::Number,
		);
	}

	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

}