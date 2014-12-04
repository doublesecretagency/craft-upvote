<?php
namespace Craft;

class UpvoteVariable
{

	// 
	public function tally($elementId)
	{
		$genericClass = 'upvote-tally';
		$uniqueClass  = 'upvote-tally-'.$elementId;
		$tally = craft()->upvote_query->tally($elementId);
		$span  = '<span class="'.$genericClass.' '.$uniqueClass.'">'.$tally.'</span>';
		return TemplateHelper::getRaw($span);
	}

	// 
	public function upvote($elementId, $domElement)
	{
		return $this->_renderIcon($elementId, $domElement, Vote::Upvote);
	}

	// 
	public function downvote($elementId, $domElement)
	{
		return $this->_renderIcon($elementId, $domElement, Vote::Downvote);
	}

	// 
	private function _renderIcon($elementId, $domElement, $vote)
	{
		// Establish basics
		$genericClass = 'upvote-vote ';
		switch ($vote) {
			case Vote::Upvote:
				$js = $this->jsUpvote($elementId);
				$genericClass .= 'upvote-upvote';
				$uniqueClass   = 'upvote-upvote-'.$elementId;
				break;
			case Vote::Downvote:
				$js = $this->jsDownvote($elementId);
				$genericClass .= 'upvote-downvote';
				$uniqueClass   = 'upvote-downvote-'.$elementId;
				break;
		}
		// Get user vote history
		if (craft()->upvote->settings['requireLogin']) {
			$history = craft()->upvote_query->userHistory();
		} else {
			$history = craft()->upvote->anonymousHistory;
		}
		// If user already voted in this direction, mark as a match
		if (array_key_exists($elementId, $history) && ($history[$elementId] == $vote)) {
			$genericClass .= ' upvote-vote-match';
		}
		// Compile DOM element
		$span = '<span onclick="'.$js.'" class="'.$genericClass.' '.$uniqueClass.'">'.$domElement.'</span>';
		return TemplateHelper::getRaw($span);
	}

	// 
	public function jsUpvote($elementId, $prefix = false)
	{
		$this->_includeJs();
		return ($prefix?'javascript:':'')."upvote.upvote($elementId)";
	}

	// 
	public function jsDownvote($elementId, $prefix = false)
	{
		$this->_includeJs();
		return ($prefix?'javascript:':'')."upvote.downvote($elementId)";
	}

	// 
	private function _includeJs()
	{
		craft()->templates->includeJsResource('upvote/js/sizzle.js');
		craft()->templates->includeJsResource('upvote/js/superagent.js');
		craft()->templates->includeJsResource('upvote/js/upvote.js');
		if (craft()->upvote->settings['allowVoteRemoval']) {
			craft()->templates->includeJsResource('upvote/js/unvote.js');
		}

		// CSRF
		if (craft()->config->get('enableCsrfProtection') === true) {
			if (!craft()->upvote->csrfIncluded) {
				$csrf = '
window.csrfTokenName = "'.craft()->config->get('csrfTokenName').'";
window.csrfTokenValue = "'.craft()->request->getCsrfToken().'";
';
				craft()->templates->includeJs($csrf);
				craft()->upvote->csrfIncluded = true;
			}
		}
	}

	// 
	public function sort(ElementCriteriaModel $entries)
	{
		return craft()->upvote_query->orderByTally($entries);
	}

}