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
 * Class CssAssets
 * @since 2.0.0
 */
class CssAssets extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->sourcePath = '@doublesecretagency/upvote/resources';

        $this->css = [
            'css/upvote.css',
        ];
    }

}
