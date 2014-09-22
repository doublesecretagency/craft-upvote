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
		}
	}

	// 
	private function _includeJs()
	{
        craft()->templates->includeJsResource('upvote/js/superagent.js');
        craft()->templates->includeJsResource('upvote/js/upvote.js');

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

	/*
	// 
	public function upvoteIcon($elementId)
	{
		return '';
	}

	// 
	public function downvoteIcon($elementId)
	{
		return '';
	}
	*/

}