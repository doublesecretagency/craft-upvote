<?php
namespace Craft;

class UpvoteVariable
{

	// 
	public function score($elementId)
	{
		$id    = 'upvote-score-'.$elementId;
		$class = 'upvote-score';
		$score = craft()->upvote_query->score($elementId);
		$span  = '<span id="'.$id.'" class="'.$class.'">'.$score.'</span>';
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
		if (craft()->upvote->settings['allowDownvoting']) {
			return $this->_renderIcon($elementId, $domElement, Vote::Downvote);
		} else {
			//$link = UrlHelper::getCpUrl().'/settings/plugins/upvote';
			//$message = 'Downvoting is disabled <a href="'.$link.'" target="_blank">(view settings)</a>';
			$message = 'Downvoting is disabled';
			return TemplateHelper::getRaw($message);
		}
	}

	// 
	public function _renderIcon($elementId, $domElement, $vote)
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
		if (craft()->upvote->settings['allowDownvoting']) {
			$this->_includeJs();
			return ($prefix?'javascript:':'').'upvote.downvote('.$elementId.')';
		} else {
			return '';
		}
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
	public function orderElementsByScore($entries)
	{
		return craft()->upvote_query->orderElementsByScore($entries);
	}

}