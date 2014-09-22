<?php
namespace Craft;

class Upvote_QueryService extends BaseApplicationComponent
{

	// 
	public function score($elementId)
	{
		$record = Upvote_ElementScoreRecord::model()->findByPK($elementId);
		return ($record ? $record->score : 0);
	}

}