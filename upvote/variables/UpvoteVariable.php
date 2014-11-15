<?php
namespace Craft;

class UpvoteVariable
{

	// 
	public function tally($elementId)
	{
		$id    = 'upvote-tally-'.$elementId;
		$class = 'upvote-tally';
		$tally = craft()->upvote_query->tally($elementId);
		$span  = '<span id="'.$id.'" class="'.$class.'">'.$tally.'</span>';
		return TemplateHelper::getRaw($span);
	}

	// 
	public function upvoteIcon($elementId, $domElement)
	{
		return $this->_renderIcon($elementId, $domElement, Vote::Upvote);
	}

	// 
	public function downvoteIcon($elementId, $domElement)
	{
		return $this->_renderIcon($elementId, $domElement, Vote::Downvote);
	}

	// 
	private function _renderIcon($elementId, $domElement, $vote)
	{
		// Establish basics
		$class = 'upvote-vote';
		switch ($vote) {
			case Vote::Upvote:
				$id = 'upvote-upvote-'.$elementId;
				$js = $this->jsUpvote($elementId);
				$class .= ' upvote-upvote';
				break;
			case Vote::Downvote:
				$id = 'upvote-downvote-'.$elementId;
				$js = $this->jsDownvote($elementId);
				$class .= ' upvote-downvote';
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
			$class .= ' upvote-vote-match';
		}
		// Compile DOM element
		$span = '<span onclick="'.$js.'" id="'.$id.'" class="'.$class.'">'.$domElement.'</span>';
		return TemplateHelper::getRaw($span);
	}

	// 
	public function jsUpvote($elementId, $prefix = false)
	{
		$this->_includeJs();
		return ($prefix?'javascript:':'').'upvote.upvote('.$elementId.')';
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
	public function mostPopular(ElementCriteriaModel $entries)
	{
		return craft()->upvote_query->orderByTally($entries);
	}

}