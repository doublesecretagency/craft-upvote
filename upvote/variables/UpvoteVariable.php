<?php
namespace Craft;

class UpvoteVariable
{

	// 
	public function test($elementId, $vote)
	{
		return craft()->upvote_vote->castVote($elementId, $vote);
	}





	// 
	public function jsUpvote($elementId)
	{
		$this->_includeJs();
		return 'upvote.upvote('.$elementId.')';
	}

	// 
	public function jsDownvote($elementId)
	{
		$this->_includeJs();
		return 'upvote.downvote('.$elementId.')';
	}

	// 
	private function _includeJs()
	{
		//$csrf = '// JS CSRF';
        //craft()->templates->includeJs($csrf, true);
        craft()->templates->includeJsResource('upvote/js/superagent.js');
        craft()->templates->includeJsResource('upvote/js/upvote.js');
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