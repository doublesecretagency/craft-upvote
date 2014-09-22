<?php
namespace Craft;

class Upvote_VoteService extends BaseApplicationComponent
{

	// 
	public function castVote($elementId, $vote)
	{

		// If login is required
		if (craft()->upvote->settings['requireLogin']) {
			// Update user history
			if (!$this->_updateUserHistoryDatabase($elementId, $vote)) {
				return false;
			}
		} else {
			// Update user cookie
			if (!$this->_updateUserHistoryCookie($elementId, $vote)) {
				return false;
			}
		}
		
		// Update element score
		$this->_updateElementScore($elementId, $vote);

		return array(
			'id'   => $elementId,
			'vote' => $vote,
		);

	}

	// 
	public function withdrawVote($elementId)
	{

		// Remove from cookie history
		unset(craft()->upvote->anonymousHistory[$elementId]);
		$this->_saveUserHistoryCookie();

		$user = craft()->userSession->getUser();
		// If user is not logged in, return false
		if ($user) {
			// Load existing element history
			$record = Upvote_UserHistoryRecord::model()->findByPK($user->id);
			// If history exists, remove vote
			if ($record) {

				// Remove from db history

				$record->save();
			}
		}


		// Subtract original vote from element total

	}

	// 
	private function _updateElementScore($elementId, $vote)
	{
		// Load existing element score
		$record = Upvote_ElementScoreRecord::model()->findByPK($elementId);
		// If no score exists, create new
		if (!$record) {
			$record = new Upvote_ElementScoreRecord;
			$record->id = $elementId;
			$record->score = 0;
		}
		// Register vote
		$record->score += $vote;
		// Save
		return $record->save();
	}

	// 
	private function _updateUserHistoryDatabase($elementId, $vote)
	{
		$user = craft()->userSession->getUser();
		// If user is not logged in, return false
		if (!$user) {
			return false;
		}
		// Load existing element history
		$record = Upvote_UserHistoryRecord::model()->findByPK($user->id);
		// If no history exists, create new
		if (!$record) {
			$record = new Upvote_UserHistoryRecord;
			$record->id = $user->id;
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

	// 
	private function _updateUserHistoryCookie($elementId, $vote)
	{
		$history =& craft()->upvote->anonymousHistory;
		// If not already voted for, cast vote
		if (!array_key_exists($elementId, $history)) {
			$history[$elementId] = $vote;
			$this->_saveUserHistoryCookie();
			return true;
		} else {
			return false;
		}

	}

	// 
	private function _saveUserHistoryCookie()
	{
		$cookie   = craft()->upvote->userCookie;
		$history  = craft()->upvote->anonymousHistory;
		$lifespan = craft()->upvote->userCookieLifespan;
		craft()->userSession->saveCookie($cookie, $history, $lifespan);
	}

}