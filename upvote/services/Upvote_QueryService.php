<?php
namespace Craft;

class Upvote_QueryService extends BaseApplicationComponent
{

	//
	public function tally($elementId, $key = null)
	{
		$record = Upvote_ElementTallyRecord::model()->findByAttributes(array(
			'elementId' => $elementId,
			'voteKey'   => $key,
		));
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

	//
	public function orderByTally(ElementCriteriaModel $criteria, $key = null) {
		// Collect and sort elementIds
		$elementIds = $this->_elementIdsByTally($key);
		if ($elementIds) {
			// Match order of criteria to elementIds
			$criteria->setAttribute('order', 'FIELD(elements.id, '.join(', ', $elementIds).') DESC');
		}
		return $criteria;
	}

	//
	private function _elementIdsByTally($key)
	{
		// Don't proceed if key isn't null, string, or numeric
		if (!is_null($key) && !is_string($key) && !is_numeric($key)) {
			return false;
		} else if (null === $key) {
			$conditions = 'tallies.voteKey IS NULL';
		} else {
			$conditions = 'tallies.voteKey = :key';
		}
		// Join with elements table to sort by tally
		$query = craft()->db->createCommand()
			->select('elements.id')
			->from('elements elements')
			->leftJoin('upvote_elementtallies tallies', 'elements.id = tallies.elementId')
			->where($conditions, array(':key' => $key))
			->order('IFNULL(tallies.tally, 0) DESC, elements.id DESC')
		;
		// Return elementIds
		$elementIds = $query->queryColumn();
		return array_reverse($elementIds);
	}

}