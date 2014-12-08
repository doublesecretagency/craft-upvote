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
		if (craft()->upvote->settings['allowDownvoting']) {
			return $this->_castVote(Vote::Downvote);
		} else {
			$this->returnJson('Downvoting is disabled.');
		}
	}

	// Swap vote on specified element
	public function actionSwap()
	{
		if (!craft()->upvote->settings['allowVoteRemoval']) {
			$this->returnJson('Unable to swap vote. Vote removal is disabled.');
		} else if (!craft()->upvote->settings['allowDownvoting']) {
			$this->returnJson('Unable to swap vote. Downvoting is disabled.');
		} else {
			$elementId = craft()->request->getPost('id');
			$response = craft()->upvote_vote->removeVote($elementId);
			if (is_array($response)) {
				return $this->_castVote($response['antivote']);
			} else {
				$this->returnJson($response);
			}
		}
	}

	// Vote on specified element
	private function _castVote($vote)
	{
		$this->requireAjaxRequest();
		$loggedIn = craft()->userSession->user;
		$loginRequired = craft()->upvote->settings['requireLogin'];
		if ($loginRequired && !$loggedIn) {
			$this->returnJson('You must be logged in to vote.');
		} else {
			$elementId = craft()->request->getPost('id');
			$response = craft()->upvote_vote->castVote($elementId, $vote);
			$this->returnJson($response);
		}
	}

	// ================================================================= //

	// Withdraw vote from specified element
	public function actionRemove()
	{
		$this->requireAjaxRequest();
		$elementId = craft()->request->getPost('id');
		$response = craft()->upvote_vote->removeVote($elementId);
		$this->returnJson($response);
	}

}
