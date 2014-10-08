<?php
namespace Craft;

class Upvote_VoteService extends BaseApplicationComponent
{

	// 
	public function castVote($elementId, $vote)
	{

		$alreadyVoted = 'You have already voted on this element.';

		// If login is required
		if (craft()->upvote->settings['requireLogin']) {
			// Update user history
			if (!$this->_updateUserHistoryDatabase($elementId, $vote)) {
				return $alreadyVoted;
			}
		} else {
			// Update user cookie
			if (!$this->_updateUserHistoryCookie($elementId, $vote)) {
				return $alreadyVoted;
			}
		}
		
		// Update element tally
		$this->_updateElementTally($elementId, $vote);

		return array(
			'id'   => $elementId,
			'vote' => $vote,
		);

	}

	// 
	private function _updateElementTally($elementId, $vote)
	{
		// Load existing element tally
		$record = Upvote_ElementTallyRecord::model()->findByPK($elementId);
		// If no tally exists, create new
		if (!$record) {
			$record = new Upvote_ElementTallyRecord;
			$record->id = $elementId;
			$record->tally = 0;
		}
		// Register vote
		$record->tally += $vote;
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

	// 
	public function removeVote($elementId)
	{
		$originalVote = false;

		$this->_removeVoteFromCookie($elementId, $originalVote);
		$this->_removeVoteFromDb($elementId, $originalVote);

		if ($originalVote) {
			$antivote = (-1 * $originalVote);
			$this->_updateElementTally($elementId, $antivote);
			return array(
				'id'       => $elementId,
				'antivote' => $antivote,
			);
		} else {
			return 'Unable to remove vote.';
		}

	}

	// 
	private function _removeVoteFromCookie($elementId, &$originalVote)
	{
		// Remove from cookie history
		$historyCookie =& craft()->upvote->anonymousHistory;
		if (array_key_exists($elementId, $historyCookie)) {
			$originalVote = $historyCookie[$elementId];
			unset($historyCookie[$elementId]);
			$this->_saveUserHistoryCookie();
		}
	}

	// 
	private function _removeVoteFromDb($elementId, &$originalVote)
	{
		$user = craft()->userSession->getUser();
		if ($user) {
			$record = Upvote_UserHistoryRecord::model()->findByPK($user->id);
			if ($record) {
				// Remove from database history
				$historyDb = $record->history;
				if (array_key_exists($elementId, $historyDb)) {
					if (!$originalVote) {
						$originalVote = $historyDb[$elementId];
					}
					unset($historyDb[$elementId]);
					$record->history = $historyDb;
					$record->save();
				}
			}
		}
	}

}