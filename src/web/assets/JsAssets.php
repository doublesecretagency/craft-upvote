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
use doublesecretagency\upvote\Upvote;

/**
 * Class JsAssets
 * @since 2.0.0
 */
class JsAssets extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->sourcePath = '@doublesecretagency/upvote/resources';

        $this->js = [
            'js/sizzle.js',
            'js/superagent.js',
            'js/upvote.js',
        ];

        // Optionally allow vote removal
        if (Upvote::$plugin->getSettings()->allowVoteRemoval) {
            $this->js[] = 'js/unvote.js';
        }
    }

}
