<?php
namespace Craft;

class UpvoteController extends BaseController
{
	protected $allowAnonymous = true;

	// Add a "Like" for specified element
	public function actionLike()
	{
		$this->requireAjaxRequest();
		$elementId = craft()->request->getPost('id');
		$response = craft()->twoThumbs->addLike($elementId);
		$this->returnJson($response);
	}

	// Add a "Dislike" for specified element
	public function actionDislike()
	{
		$this->requireAjaxRequest();
		$elementId = craft()->request->getPost('id');
		$response = craft()->twoThumbs->addDislike($elementId);
		$this->returnJson($response);
	}

}
