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
use doublesecretagency\upvote\models\Settings;
use doublesecretagency\upvote\services\Vote;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class VoteController
 * @since 2.0.0
 */
class VoteController extends Controller
{

    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = true;

    /**
     * Upvote specified element.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionUpvote(): Response
    {
        $this->requirePostRequest();

        // Cast upvote
        return $this->_castVote(Upvote::UPVOTE);
    }

    /**
     * Downvote specified element.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionDownvote(): Response
    {
        $this->requirePostRequest();

        /** @var Settings $settings */
        $settings = Upvote::$plugin->getSettings();

        // If downvoting is prohibited, bail
        if (!$settings->allowDownvoting) {
            return $this->asJson('Downvoting is disabled.');
        }

        // Cast downvote
        return $this->_castVote(Upvote::DOWNVOTE);
    }

    /**
     * Swap vote on specified element.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSwap(): Response
    {
        $this->requirePostRequest();

        /** @var Settings $settings */
        $settings = Upvote::$plugin->getSettings();

        /** @var Vote $vote */
        $vote = Upvote::$plugin->upvote_vote;

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
        $response = $vote->removeVote($elementId, $key);

        // If message is returned, bail
        if (!is_array($response)) {
            return $this->asJson($response);
        }

        // Cast antivote
        return $this->_castVote($response['antivote']);
    }

    /**
     * Vote on specified element.
     *
     * @param int $value
     * @return Response
     */
    private function _castVote(int $value): Response
    {
        /** @var Settings $settings */
        $settings = Upvote::$plugin->getSettings();

        /** @var Vote $vote */
        $vote = Upvote::$plugin->upvote_vote;

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
        $response = $vote->castVote($elementId, $key, $value);

        // Return response
        return $this->asJson($response);
    }

    // ========================================================================= //

    /**
     * Withdraw vote from specified element.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionRemove(): Response
    {
        $this->requirePostRequest();

        /** @var Vote $vote */
        $vote = Upvote::$plugin->upvote_vote;

        // Get request
        $request = Craft::$app->getRequest();

        // Get POST values
        $elementId = $request->getBodyParam('id');
        $key       = $request->getBodyParam('key');

        // Remove vote
        $response = $vote->removeVote($elementId, $key);
        return $this->asJson($response);
    }

}
