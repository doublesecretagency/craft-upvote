<?php
namespace Craft;

class UpvoteVariable
{

	// Display total likes of element
	public function likes($elementId)
	{
		return craft()->twoThumbs->totalLikes($elementId);
	}

	// Display total dislikes of element
	public function dislikes($elementId)
	{
		return craft()->twoThumbs->totalDislikes($elementId);
	}

	// Display sum total value of element
	public function sum($elementId)
	{
		return craft()->twoThumbs->totalValue($elementId);
	}

	// Whether or not the element has been voted on
	public function voted($elementId)
	{
		return craft()->twoThumbs->hasVoted($elementId);
	}

	// Whether the element was voted in agreement with button
	public function votedFor($elementId, $vote)
	{
		$voteWeight = craft()->twoThumbs->voteWeight($vote);
		$voteValue = craft()->twoThumbs->voteValue($elementId);
		return ($voteWeight === $voteValue);
	}

	/*
	// Most popular item(s) in specified time period
	public function mostPopular($startTime = null, $endTime = null, $limit = 1)
	{
		return craft()->twoThumbs->mostPopular($startTime, $endTime, $limit);
	}
	*/

}