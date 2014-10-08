<?php
namespace Craft;

class Upvote_QueryService extends BaseApplicationComponent
{

	// 
	public function tally($elementId)
	{
		$record = Upvote_ElementTallyRecord::model()->findByPK($elementId);
		return ($record ? $record->tally : 0);
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


	/*
	// 
	public function orderByTally(ElementCriteriaModel $criteria) {
		$query = craft()->elements->buildElementsQuery($criteria);
		$query->join('upvote_elementtallies upvote_elementtallies', 'upvote_elementtallies.id = elements.id');
		$query->order('upvote_elementtallies.tally DESC, upvote_elementtallies.id ASC');
		return $query->queryAll();
	}
	*/

	//
	public function orderByTally(ElementCriteriaModel $criteria) {
		$elementIds = $this->_elementIdsByTally();
		$criteria->setAttribute('id', $elementIds);
		$criteria->setAttribute('order', 'FIELD(elements.id, '.join(', ', $elementIds).')');
		return $criteria;
	}

	//
	private function _elementIdsByTally() {
		$ranking = Upvote_ElementTallyRecord::model()->findAll(array(
			'order' => 'tally DESC, id ASC'
		));
		$elementIds = array();
		foreach ($ranking as $element) {
			$elementIds[] = $element->id;
		}
		return $elementIds;
	}

}