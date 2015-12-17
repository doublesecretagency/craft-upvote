<?php
namespace Craft;

class UpvoteVariable
{

	private $_disabled = array();

	private $_cssIncluded = false;
	private $_jsIncluded  = false;

	//
	public function tally($elementId, $key = null)
	{
		$genericClass = 'upvote-tally';
		$uniqueClass  = 'upvote-tally-'.$elementId;
		$tally = craft()->upvote_query->tally($elementId);
		$span  = '<span class="'.$genericClass.' '.$uniqueClass.'">'.$tally.'</span>';
		return TemplateHelper::getRaw($span);
	}

	//
	public function upvote($elementId, $key = null)
	{
		return $this->_renderIcon($elementId, $key, Vote::Upvote);
	}

	//
	public function downvote($elementId, $key = null)
	{
		return $this->_renderIcon($elementId, $key, Vote::Downvote);
	}

	//
	private function _renderIcon($elementId, $key = null, $vote)
	{

		$this->_includeCss();

		// Establish basics
		$genericClass = 'upvote-vote ';
		switch ($vote) {
			case Vote::Upvote:
				$icon = craft()->upvote_vote->upvoteIcon;
				$js = $this->jsUpvote($elementId, $key);
				$genericClass .= 'upvote-upvote';
				$uniqueClass   = 'upvote-upvote-'.$elementId.($key ? '-'.$key : '');
				break;
			case Vote::Downvote:
				$icon = craft()->upvote_vote->downvoteIcon;
				$js = $this->jsDownvote($elementId, $key);
				$genericClass .= 'upvote-downvote';
				$uniqueClass   = 'upvote-downvote-'.$elementId.($key ? '-'.$key : '');
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
		$span = '<span onclick="'.$js.'" class="'.$genericClass.' '.$uniqueClass.'">'.$icon.'</span>';
		return TemplateHelper::getRaw($span);
	}

	//
	public function jsUpvote($elementId, $key = null, $prefix = false)
	{
		$this->_includeJs();
		return ($prefix?'javascript:':'')."upvote.upvote($elementId)";
	}

	//
	public function jsDownvote($elementId, $key = null, $prefix = false)
	{
		$this->_includeJs();
		return ($prefix?'javascript:':'')."upvote.downvote($elementId)";
	}

	// Include CSS
	private function _includeCss()
	{
		// If CSS is enabled and not yet included
		if (!$this->_cssIncluded && !in_array('css', $this->_disabled)) {

			// Include CSS resources
			if (craft()->upvote->settings['allowFontAwesome']) {
				craft()->templates->includeCssResource('upvote/css/font-awesome.min.css');
			}
			craft()->templates->includeCssResource('upvote/css/upvote.css');

			// Mark CSS as included
			$this->_cssIncluded = true;
		}
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

	// ========================================================================

	// Customize icons
	public function setIcons($iconMap = array())
	{
		return craft()->upvote_vote->setIcons($iconMap);
	}

	// Sort by "highest rated"
	public function sort(ElementCriteriaModel $entries, $key = null)
	{
		return craft()->upvote_query->orderByTally($entries, $key);
	}

	// Disable native CSS and/or JS
	public function disable($resources = array())
	{
		if (is_string($resources)) {
			$resources = array($resources);
		}
		if (is_array($resources)) {
			return $this->_disabled = array_map('strtolower', $resources);
		} else {
			return false;
		}
	}

}