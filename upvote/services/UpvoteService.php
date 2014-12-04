<?php
namespace Craft;

class UpvoteService extends BaseApplicationComponent
{

	public $settings;
	
	public $userCookie = 'VoteHistory';
	public $userCookieLifespan = 315569260; // Lasts 10 years
	public $anonymousHistory;

	public $csrfIncluded = false;

	public function getAnonymousHistory()
	{
		$this->anonymousHistory = craft()->userSession->getStateCookieValue($this->userCookie);
		if (!$this->anonymousHistory) {
			$this->anonymousHistory = array();
			craft()->userSession->saveCookie($this->userCookie, array(), $this->userCookieLifespan);
		}
	}

	// Initialize tally for specified element
	public function initElementTally($elementId, $new = true)
	{
		if ($new) {
			$record = new Upvote_ElementTallyRecord;
			$record->id = $elementId;
			$record->tally = 0;
			$record->save();
		}
	}

	// Initialize tally for every existing element
	public function initAllElementTallies()
	{
		// Get all element ids
		$elementIds = craft()->db->createCommand()
			->select('id')
			->from('elements')
			->order('id')
			->queryColumn();

		// Loop through all elements
		foreach ($elementIds as $elementId) {
			$this->initElementTally($elementId);
		}
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