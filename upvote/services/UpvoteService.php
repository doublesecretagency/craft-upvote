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

	// 
	public function initElementTally($element, $new = true)
	{
		if ($new) {
			$record = new Upvote_ElementTallyRecord;
			$record->id = $element->id;
			$record->tally = 0;
			$record->save();
		}
	}

}