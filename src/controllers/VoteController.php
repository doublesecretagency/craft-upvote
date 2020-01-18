<?php
/**
 * Upvote plugin for Craft CMS
 *
 * Lets your users upvote/downvote, "like", or favorite any type of element.
 *
 * @author    Double Secret Agency
 * @link      https://www.doublesecretagency.com/
 * @copyright Copyright (c) 2014 Double Secret Agency
 */

namespace doublesecretagency\upvote\controllers;

use Craft;
use craft\web\Controller;

use doublesecretagency\upvote\Upvote;

/**
 * Class VoteController
 * @since 2.0.0
 */
class VoteController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = true;

    // Upvote specified element
    public function actionUpvote()
    {
        $this->requirePostRequest();

        // Cast upvote
        return $this->_castVote(Upvote::UPVOTE);
    }

    // Downvote specified element
    public function actionDownvote()
    {
        $this->requirePostRequest();

        // Get settings
        $settings = Upvote::$plugin->getSettings();

        // If downvoting is prohibited, bail
        if (!$settings->allowDownvoting) {
            return $this->asJson('Downvoting is disabled.');
        }

        // Cast downvote
        return $this->_castVote(Upvote::DOWNVOTE);
    }

    // Swap vote on specified element
    public function actionSwap()
    {
        $this->requirePostRequest();

        // Get settings
        $settings = Upvote::$plugin->getSettings();

        // If vote removal is prohibited, bail
        if (!$settings->allowVoteRemoval) {
            return $this->asJson('Unable to swap vote. Vote removal is disabled.');
        }

        // If downvoting is prohibited, bail
        if (!$settings->allowDownvoting) {
            return $this->asJson('Unable to swap vote. Downvoting is disabled.');
        }

        // Get request
        $request = Craft::$app->getRequest();

        // Get POST values
        $elementId = $request->getBodyParam('id');
        $key       = $request->getBodyParam('key');

        // Attempt to remove vote
        $response = Upvote::$plugin->upvote_vote->removeVote($elementId, $key);

        // If message is returned, bail
        if (!is_array($response)) {
            return $this->asJson($response);
        }

        // Cast antivote
        return $this->_castVote($response['antivote']);
    }

    // Vote on specified element
    private function _castVote($value)
    {
        // Get settings
        $settings = Upvote::$plugin->getSettings();

        // Get current user & login requirement
        $currentUser   = Craft::$app->user->getIdentity();
        $loginRequired = $settings->requireLogin;

        // Check if login is required
        if ($loginRequired && !$currentUser) {
            return $this->asJson('You must be logged in to vote.');
        }

        // Get request
        $request = Craft::$app->getRequest();

        // Get POST values
        $elementId = $request->getBodyParam('id');
        $key       = $request->getBodyParam('key');

        // Cast vote
        $response = Upvote::$plugin->upvote_vote->castVote($elementId, $key, $value);

        // Return response
        return $this->asJson($response);
    }

    // ================================================================= //

    // Withdraw vote from specified element
    public function actionRemove()
    {
        $this->requirePostRequest();

        // Get request
        $request = Craft::$app->getRequest();

        // Get POST values
        $elementId = $request->getBodyParam('id');
        $key       = $request->getBodyParam('key');

        // Remove vote
        $response = Upvote::$plugin->upvote_vote->removeVote($elementId, $key);
        return $this->asJson($response);
    }

}
