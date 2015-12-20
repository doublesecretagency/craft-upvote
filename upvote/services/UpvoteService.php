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

}