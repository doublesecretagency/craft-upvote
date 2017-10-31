<?php
namespace Craft;

class UpvoteService extends BaseApplicationComponent
{

	public $settings;

	public $userCookie = 'VoteHistory';
	public $userCookieLifespan = 315569260; // Lasts 10 years
	public $anonymousHistory;

	public $csrfIncluded = false;

	// Generate combined item key
	public function setItemKey($elementId, $key)
	{
		return $elementId.($key ? ':'.$key : '');
	}

	// Get history of anonymous user
	public function getAnonymousHistory()
	{
		$this->anonymousHistory = craft()->userSession->getStateCookieValue($this->userCookie);
		if (!$this->anonymousHistory) {
			$this->anonymousHistory = array();
			craft()->userSession->saveCookie($this->userCookie, array(), $this->userCookieLifespan);
		}
	}

	// Check if a key is valid
	public function validKey($key)
	{
		return (is_null($key) || is_string($key) || is_numeric($key));
	}

	// Coming Soon
	//  - Will allow complex vote filtering,
	//    based on detailed vote log
	/*
	public function getDetailedVotes($params) {
		$params = array(
			'id' => '',
			'elementId' => '',
			'userId' => '',
			'ipAddress' => '',
			'voteValue' => '',
			'wasUnvote' => '',
			'startDateTime' => '',
			'endDateTime' => '',
		);
	}
	*/

	// ========================================================================= //

	/**
	 * Fires an 'onBeforeVote' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeVote(Event $event)
	{
		$this->raiseEvent('onBeforeVote', $event);
	}

	/**
	 * Fires an 'onVote' event.
	 *
	 * @param Event $event
	 */
	public function onVote(Event $event)
	{
		$this->raiseEvent('onVote', $event);
	}

	/**
	 * Fires an 'onBeforeUnvote' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeUnvote(Event $event)
	{
		$this->raiseEvent('onBeforeUnvote', $event);
	}

	/**
	 * Fires an 'onUnvote' event.
	 *
	 * @param Event $event
	 */
	public function onUnvote(Event $event)
	{
		$this->raiseEvent('onUnvote', $event);
	}

	// ========================================================================= //

	// Does the plugin contain legacy data?
	public function hasLegacyData()
	{
		return (bool) Upvote_ElementTotalRecord::model()->countByAttributes(array(), 'legacyTotal <> 0');
	}

}