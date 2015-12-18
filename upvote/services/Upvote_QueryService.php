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
			$conditions = 'voteKey IS NULL';
		} else {
			$conditions = 'voteKey = :key';
		}
		// Get matching ratings
		$query = craft()->db->createCommand()
			->select('elementId')
			->from('upvote_elementtallies')
			->where($conditions, array(':key' => $key))
			->order('tally desc, dateUpdated desc')
		;
		// Return elementIds
		$elementIds = $query->queryColumn();
		return array_reverse($elementIds);
	}

}