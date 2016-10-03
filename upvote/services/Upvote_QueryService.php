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

	// ========================================================================


	// NEEDS A MIGRATION TO CREATE THESE COLUMNS
	// ALSO NEEDS A SERVICE TO STORE THOSE VALUES

	//
	public function totalUpvotes($elementId, $key)
	{
		$record = Upvote_ElementTallyRecord::model()->findByAttributes(array(
			'elementId' => $elementId,
			'voteKey'   => $key,
		));
		return ($record ? $record->totalUpvotes : 0);
	}

	//
	public function totalDownvotes($elementId, $key)
	{
		$record = Upvote_ElementTallyRecord::model()->findByAttributes(array(
			'elementId' => $elementId,
			'voteKey'   => $key,
		));
		return ($record ? $record->totalDownvotes : 0);
	}

	//
	public function totalVotes($elementId, $key)
	{
		$record = Upvote_ElementTallyRecord::model()->findByAttributes(array(
			'elementId' => $elementId,
			'voteKey'   => $key,
		));
		return ($record ? $record->totalVotes : 0);
	}

	// ========================================================================

	//
	public function userHistory($user = null)
	{
		// If no user specified, get current user
		if (!$user) {
			$user = craft()->userSession->getUser();
		}
		// If user exists, get their history
		if ($user) {
			$record = Upvote_UserHistoryRecord::model()->findByPK($user->id);
			if ($record) {
				return $record->history;
			}
		}
		// If still nothing, return empty array
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
		// Don't proceed if key isn't valid
		if (!craft()->upvote->validKey($key)) {
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
			->leftJoin('upvote_elementtallies tallies', 'elements.id = tallies.elementId AND '.$conditions, array(':key' => $key))
			->order('IFNULL(tallies.tally, 0) DESC, elements.id DESC')
		;
		// Return elementIds
		$elementIds = $query->queryColumn();
		return array_reverse($elementIds);
	}

}