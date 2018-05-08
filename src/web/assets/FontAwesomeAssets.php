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

namespace doublesecretagency\upvote\web\assets;

use craft\web\AssetBundle;

/**
 * Class FontAwesomeAssets
 * @since 2.0.0
 */
class FontAwesomeAssets extends AssetBundle
{

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        $this->sourcePath = '@vendor/fortawesome/font-awesome';

        $this->css = [
            'css/font-awesome.min.css',
        ];
    }

}