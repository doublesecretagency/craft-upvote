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

	// 
	public function userHistory()
	{
		$user = craft()->userSession->getUser();
		if ($user) {
			$record = Upvote_UserHistoryRecord::model()->findByPK($user->id);
			if ($record) {
				return $record->history;
			}
		}
		return array();
	}


	// 
	public function orderElementsByScore(ElementCriteriaModel $criteria) {
		$query = craft()->elements->buildElementsQuery($criteria);
		$query->join('upvote_elementscores upvote_elementscores', 'upvote_elementscores.id = elements.id');
		$query->order('upvote_elementscores.score DESC, upvote_elementscores.id ASC');
		return $query->queryAll();
	}

}