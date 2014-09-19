<?php
namespace Craft;

class Upvote_VoteService extends BaseApplicationComponent
{

	// 
	public function castVote($elementId, $vote)
	{

		// If login is required
		if (craft()->upvote->settings['requireLogin']) {
			$user = craft()->userSession->getUser();
			// If user is logged in
			if ($user) {
				// Update user history
				if (!$this->_updateUserHistory($user->id, $elementId, $vote)) {
					return false;
				}
			} else {
				return false;
			}
		}
		
		// Update element score
		$this->_updateElementScore($elementId, $vote);

		return array(
			'elementId' => $elementId,
			'vote' => $vote,
		);

	}

	// 
	private function _updateElementScore($elementId, $vote)
	{
		// Load existing element score
		$record = Upvote_ElementScoreRecord::model()->findByAttributes(array(
			'elementId' => $elementId,
		));
		// If no score exists, create new
		if (!$record) {
			$record = new Upvote_ElementScoreRecord;
			$record->elementId = $elementId;
			$record->score = 0;
		}
		// Register vote
		$record->score += $vote;
		// Save
		return $record->save();
	}

	// 
	private function _updateUserHistory($userId, $elementId, $vote)
	{
		// Load existing element history
		$record = Upvote_UserHistoryRecord::model()->findByAttributes(array(
			'userId' => $userId,
		));
		// If no history exists, create new
		if (!$record) {
			$record = new Upvote_UserHistoryRecord;
			$record->userId = $userId;
			$history = array();
		// Else if user already voted on element, return false
		} else if (array_key_exists($elementId, $record->history)) {
			return false;
		// Else, add vote to history
		} else {
			$history = $record->history;
		}
		// Register vote
		$history[$elementId] = $vote;
		$record->history = $history;
		// Save
		return $record->save();
	}

}