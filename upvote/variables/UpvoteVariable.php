<?php
namespace Craft;

class UpvoteVariable
{

	private $_disabled = array();

	private $_cssIncluded = false;
	private $_jsIncluded  = false;

	//
	public function userHistory($user = null)
	{
		return craft()->upvote_query->userHistory($user);
	}

	//
	public function tally($elementId, $key = null)
	{
		$genericClass = 'upvote-tally';
		$uniqueClass  = 'upvote-tally-'.$elementId.($key ? '-'.$key : '');
		$tally = craft()->upvote_query->tally($elementId, $key);
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
		$item = craft()->upvote->setItemKey($elementId, $key);
		if (array_key_exists($item, $history) && ($history[$item] == $vote)) {
			$genericClass .= ' upvote-vote-match';
		}
		// Compile DOM element
		$span = '<span onclick="'.$js.'" class="'.$genericClass.' '.$uniqueClass.'">'.$icon.'</span>';
		return TemplateHelper::getRaw($span);
	}

	//
	public function jsUpvote($elementId, $key = null, $prefix = false)
	{
		if (craft()->upvote->validKey($key)) {
			$this->_includeJs();
			$key = ($key ? "'$key'" : "null");
			return ($prefix?'javascript:':'')."upvote.upvote($elementId, $key)";
		}
	}

	//
	public function jsDownvote($elementId, $key = null, $prefix = false)
	{
		if (craft()->upvote->validKey($key)) {
			$this->_includeJs();
			$key = ($key ? "'$key'" : "null");
			return ($prefix?'javascript:':'')."upvote.downvote($elementId, $key)";
		}
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

	// Include JS
	private function _includeJs()
	{
		// If JS is enabled and not yet included
		if (!$this->_jsIncluded && !in_array('js', $this->_disabled)) {

			// Include JS resources
			craft()->templates->includeJsResource('upvote/js/sizzle.js');
			craft()->templates->includeJsResource('upvote/js/superagent.js');
			craft()->templates->includeJsResource('upvote/js/upvote.js');

			// Allow Vote Removal
			if (craft()->upvote->settings['allowVoteRemoval']) {
				craft()->templates->includeJsResource('upvote/js/unvote.js');
			}

			// Dev Mode
			if (craft()->config->get('devMode')) {
				craft()->templates->includeJs('upvote.devMode = true;');
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

			// Mark JS as included
			$this->_jsIncluded = true;

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