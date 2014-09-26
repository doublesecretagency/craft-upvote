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



	public function sort(ElementCriteriaModel $criteria) {

		$elementIds = $this->_elementIdsByScore();

		// Filter only elements with matching ids
		$criteria->setAttribute('id', $elementIds);

		// Sort results by ordered element ids
		$criteria->setAttribute('order', 'FIELD(elements.id, '.join(', ', $elementIds).')');

		return $criteria;
	}

	private function _elementIdsByScore() {
		$scores = Upvote_ElementScoreRecord::model()->ordered()->findAll();

		$elementIds = array();
		foreach ($scores as $score) {
			$elementIds[] = $score->id;
		}

		return $elementIds;
	}

}