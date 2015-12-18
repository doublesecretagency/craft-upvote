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

	//
	public function castVote($elementId, $key, $vote)
	{

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
		$this->_updateElementTally($elementId, $key, $vote);
		$this->_updateVoteLog($elementId, $key, $vote);

		return array(
			'id'   => $elementId,
			'key'  => $key,
			'vote' => $vote,
		);

	}

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
	private function _updateElementTally($elementId, $key, $vote)
	{
		// Load existing element tally
		$record = Upvote_ElementTallyRecord::model()->findByAttributes(array(
			'elementId' => $elementId,
			'voteKey'   => $key,
		));
		// If no tally exists, create new
		if (!$record) {
			$record = new Upvote_ElementTallyRecord;
			$record->elementId = $elementId;
			$record->voteKey   = $key;
			$record->tally     = 0;
		}
		// Register vote
		$record->tally += $vote;
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
	public function removeVote($elementId, $key)
	{
		$originalVote = false;

		$this->_removeVoteFromCookie($elementId, $key, $originalVote);
		$this->_removeVoteFromDb($elementId, $key, $originalVote);

		if ($originalVote) {
			$antivote = (-1 * $originalVote);
			$this->_updateElementTally($elementId, $key, $antivote);
			$this->_updateVoteLog($elementId, $key, $antivote, true);
			return array(
				'id'       => $elementId,
				'key'      => $key,
				'antivote' => $antivote,
			);
		} else {
			return 'Unable to remove vote.';
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