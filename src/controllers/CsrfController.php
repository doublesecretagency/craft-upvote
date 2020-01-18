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
use yii\web\Response;

/**
 * Class CsrfController
 * @since 2.1.0
 */
class CsrfController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = true;

    /**
     * Generate a valid CSRF token & name.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $request = Craft::$app->getRequest();
        return $this->asJson([
            $request->csrfParam => $request->getCsrfToken()
        ]);
    }

}
