<?php
namespace Craft;

class Upvote_VoteService extends BaseApplicationComponent
{

	public $upvoteIcon;
	public $downvoteIcon;

	public $alreadyVoted = 'You have already voted on this element.';

	//
	public function init()
	{
		$this->_loadIcons();
	}

	//
	private function _loadIcons()
	{
		$this->upvoteIcon   = $this->_fa('caret-up');
		$this->downvoteIcon = $this->_fa('caret-down');
	}

	//
	private function _fa($iconType)
	{
		return '<i class="fa fa-'.$iconType.' fa-2x"></i>';
	}

	//
	public function setIcons($iconMap = array())
	{
		foreach ($iconMap as $type => $html) {
			switch ($type) {
				case 'up'   : $this->upvoteIcon   = $html; break;
				case 'down' : $this->downvoteIcon = $html; break;
			}
		}
	}

	// ========================================================================= //

	//
	public function castVote($elementId, $key, $vote)
	{
		// Prep return data
		$returnData = array(
			'id'   => $elementId,
			'key'  => $key,
			'vote' => $vote,
		);

		// Fire an 'onBeforeVote' event
		craft()->upvote->onBeforeVote(new Event($this, $returnData));

		// If login is required
		if (craft()->upvote->settings['requireLogin']) {
			// Update user history
			if (!$this->_updateUserHistoryDatabase($elementId, $key, $vote)) {
				return $this->alreadyVoted;
			}
		} else {
			// Update user cookie
			if (!$this->_updateUserHistoryCookie($elementId, $key, $vote)) {
				return $this->alreadyVoted;
			}
		}

		// Update element tally
		$this->_updateElementTotals($elementId, $key, $vote);
		$this->_updateVoteLog($elementId, $key, $vote);

		// Fire an 'onVote' event
		craft()->upvote->onVote(new Event($this, $returnData));

		return $returnData;

	}

	//
	public function removeVote($elementId, $key)
	{
		// Prep return data
		$returnData = array(
			'id'  => $elementId,
			'key' => $key,
		);

		// Fire an 'onBeforeUnvote' event
		craft()->upvote->onBeforeUnvote(new Event($this, $returnData));

		//
		// FLAW:
		// It's impossible to know the value of $originalVote before killing cookie/DB.
		// Therefore, $antivote can't be contained in the 'onBeforeUnvote' event.
		//

		$originalVote = false;

		$this->_removeVoteFromCookie($elementId, $key, $originalVote);
		$this->_removeVoteFromDb($elementId, $key, $originalVote);

		if ($originalVote) {
			$antivote = (-1 * $originalVote);
			$returnData['antivote'] = $antivote;
			$this->_updateElementTotals($elementId, $key, $antivote, true);
			$this->_updateVoteLog($elementId, $key, $antivote, true);

			// Fire an 'onUnvote' event
			craft()->upvote->onUnvote(new Event($this, $returnData));

			return $returnData;
		} else {
			return 'Unable to remove vote.';
		}

	}

	// ========================================================================= //

	//
	private function _updateUserHistoryDatabase($elementId, $key, $vote)
	{
		$user = craft()->userSession->getUser();
		// If user is not logged in, return false
		if (!$user) {
			return false;
		}
		// Load existing element history
		$record = Upvote_UserHistoryRecord::model()->findByPK($user->id);
		$item = craft()->upvote->setItemKey($elementId, $key);
		// If no history exists, create new
		if (!$record) {
			$record = new Upvote_UserHistoryRecord;
			$record->id = $user->id;
			$history = array();
		// Else if user already voted on element, return false
		} else if (array_key_exists($item, $record->history)) {
			return false;
		// Else, add vote to history
		} else {
			$history = $record->history;
		}
		// Register vote
		$history[$item] = $vote;
		$record->history = $history;
		// Save
		return $record->save();
	}

	//
	private function _updateUserHistoryCookie($elementId, $key, $vote)
	{
		$history =& craft()->upvote->anonymousHistory;
		$item = craft()->upvote->setItemKey($elementId, $key);
		// If not already voted for, cast vote
		if (!array_key_exists($item, $history)) {
			$history[$item] = $vote;
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
	private function _updateElementTotals($elementId, $key, $vote, $antivote = false)
	{
		// Load existing element totals
		$record = Upvote_ElementTotalRecord::model()->findByAttributes(array(
			'elementId' => $elementId,
			'voteKey'   => $key,
		));
		// If no totals record exists, create new
		if (!$record) {
			$record = new Upvote_ElementTotalRecord;
			$record->elementId     = $elementId;
			$record->voteKey       = $key;
			$record->upvoteTotal   = 0;
			$record->downvoteTotal = 0;
		}
		// If vote is being removed
		if ($antivote) {
			// Vote direction
			$antiUpvote   = (-1 == $vote);
			$antiDownvote = ( 1 == $vote);
			// Whether to remove a legacy vote
			$removeLegacyUpvote   = ($antiUpvote   && $record->legacyTotal > 0);
			$removeLegacyDownvote = ($antiDownvote && $record->legacyTotal < 0);
			// If removing legacy vote
			if ($removeLegacyUpvote) {
				$record->legacyTotal--;
			} else if ($removeLegacyDownvote) {
				$record->legacyTotal++;
			} else {
				// Register unvote (default behavior)
				switch ($vote) {
					case  1:
						$record->downvoteTotal--;
						break;
					case -1:
						$record->upvoteTotal--;
						break;
				}
			}
		} else {
			// Register vote
			switch ($vote) {
				case  1:
					$record->upvoteTotal++;
					break;
				case -1:
					$record->downvoteTotal++;
					break;
			}
		}
		// Save
		return $record->save();
	}

	//
	private function _updateVoteLog($elementId, $key, $vote, $unvote = false)
	{
		if (craft()->upvote->settings['keepVoteLog']) {
			$currentUser = craft()->userSession->getUser();
			$record = new Upvote_VoteLogRecord;
			$record->elementId = $elementId;
			$record->voteKey   = $key;
			$record->userId    = ($currentUser ? $currentUser->id : null);
			$record->ipAddress = $_SERVER['REMOTE_ADDR'];
			$record->voteValue = $vote;
			$record->wasUnvote = (int) $unvote;
			$record->save();
		}
	}

	//
	private function _removeVoteFromCookie($elementId, $key, &$originalVote)
	{
		// Remove from cookie history
		$historyCookie =& craft()->upvote->anonymousHistory;
		$item = craft()->upvote->setItemKey($elementId, $key);
		if (array_key_exists($item, $historyCookie)) {
			$originalVote = $historyCookie[$item];
			unset($historyCookie[$item]);
			$this->_saveUserHistoryCookie();
		}
	}

	//
	private function _removeVoteFromDb($elementId, $key, &$originalVote)
	{
		$user = craft()->userSession->getUser();
		if ($user) {
			$record = Upvote_UserHistoryRecord::model()->findByPK($user->id);
			if ($record) {
				// Remove from database history
				$historyDb = $record->history;
				$item = craft()->upvote->setItemKey($elementId, $key);
				if (array_key_exists($item, $historyDb)) {
					if (!$originalVote) {
						$originalVote = $historyDb[$item];
					}
					unset($historyDb[$item]);
					$record->history = $historyDb;
					$record->save();
				}
			}
		}
	}

}