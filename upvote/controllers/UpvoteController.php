<?php
namespace Craft;

class UpvoteController extends BaseController
{
	protected $allowAnonymous = true;

	// Upvote specified element
	public function actionUpvote()
	{
		return $this->_castVote(Vote::Upvote);
	}

	// Downvote specified element
	public function actionDownvote()
	{
		return $this->_castVote(Vote::Downvote);
	}

	// Vote on specified element
	private function _castVote($vote)
	{
		$this->requireAjaxRequest();
		$elementId = craft()->request->getPost('id');
		$response = craft()->upvote_vote->castVote($elementId, $vote);
		$this->returnJson($response);
	}

}
